<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScanResource;
use App\Models\Exam;
use App\Models\Scan;
use App\Services\ScanScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ScanController extends Controller
{
    public function __construct(private ScanScoringService $scoring) {}

    public function index(Request $request, Exam $exam): JsonResponse
    {
        abort_if($exam->user_id !== $request->user()->id, 404);

        $scans = Scan::where('exam_id', $exam->id)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->with(['exam.answerKeys', 'student'])
            ->latest()
            ->take(500)
            ->get();

        return ScanResource::collection($scans)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exam_id' => ['required', 'exists:exams,id'],
            'booklet' => ['required', 'string', 'size:1'],
            'student_no' => ['required', 'string', 'max:20'],
            'answers' => ['required', 'array'],
            'confidence' => ['nullable', 'integer', 'min:0', 'max:100'],
            'device_id' => ['nullable', 'string', 'max:64'],
            'qr_payload' => ['nullable', 'string', 'max:64'],
            'paper_image' => ['nullable', 'image', 'max:6144'],
        ]);

        $exam = Exam::findOrFail($validated['exam_id']);
        abort_if($exam->user_id !== $request->user()->id, 404);
        abort_unless($exam->status === 'published', 422, 'Sınav yayında değil.');

        $booklet = strtoupper($validated['booklet']);
        abort_unless(in_array($booklet, $exam->booklets ?? [], true), 422, 'Bu sınavda böyle bir kitapçık yok.');

        $formVersion = $this->resolveFormVersion($exam, $validated['qr_payload'] ?? null, $booklet);

        $key = $exam->answerKeys()->where('booklet', $booklet)->first();
        abort_unless($key, 422, 'Bu kitapçık için cevap anahtarı girilmemiş.');

        $duplicate = Scan::where('exam_id', $exam->id)
            ->where('student_no_raw', $validated['student_no'])
            ->where('booklet', $booklet)
            ->first();

        if ($duplicate) {
            return response()->json([
                'message' => 'Bu öğrenci için bu kitapçık zaten okutulmuş.',
                'scan_id' => $duplicate->id,
            ], 409);
        }

        $student = $exam->class_id
            ? $exam->schoolClass->students()->where('student_no', $validated['student_no'])->first()
            : null;

        $result = $this->scoring->score($exam, $key, $validated['answers']);

        $confidence = $validated['confidence'] ?? 100;
        $needsReview = $result['review_items'] !== []
            || $student === null && $exam->class_id !== null
            || $confidence < ScanScoringService::REVIEW_CONFIDENCE_THRESHOLD;

        $scan = Scan::create([
            'exam_id' => $exam->id,
            'student_id' => $student?->id,
            'student_no_raw' => $validated['student_no'],
            'booklet' => $booklet,
            'answers' => $validated['answers'],
            'score' => $result['score'],
            'max_score' => $result['max_score'],
            'confidence' => $confidence,
            'status' => $needsReview ? 'review' : 'ok',
            'scanned_by' => $request->user()->id,
            'device_id' => $validated['device_id'] ?? null,
        ]);

        if ($request->hasFile('paper_image')) {
            $scan->paper_image_path = $request->file('paper_image')->store("papers/{$exam->id}", 'public');
            $scan->save();
        }

        $scan->load(['exam.answerKeys', 'student']);

        return (new ScanResource($scan))->response()->setStatusCode(201);
    }

    private function resolveFormVersion(Exam $exam, ?string $qrPayload, string $booklet): int
    {
        $latest = $exam->formTemplates()->latest('version')->first();
        abort_unless($latest, 422, 'Bu sınav için basılabilir form şablonu yok.');

        if ($qrPayload === null) {
            return $latest->version;
        }

        try {
            $qr = $this->scoring->parseQrPayload($qrPayload);
        } catch (InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }

        abort_unless($qr['exam_id'] === $exam->id, 422, 'QR kodu başka bir sınava ait.');
        abort_unless($qr['booklet'] === $booklet, 422, 'QR kodu başka bir kitapçığa ait.');
        abort_unless(
            hash_equals($this->scoring->qrChecksum($qr['exam_id'], $qr['version'], $qr['booklet']), $qr['checksum']),
            422,
            'QR doğrulaması başarısız.'
        );

        if ($qr['version'] !== $latest->version) {
            abort(410, 'Form güncellenmiş. Lütfen güncel formu yazdırın.');
        }

        return $latest->version;
    }
}
