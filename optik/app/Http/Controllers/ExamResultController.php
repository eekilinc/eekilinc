<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamResultController extends Controller
{
    public function index(Request $request, Exam $exam): View
    {
        abort_if($exam->user_id !== $request->user()->id, 404);

        $status = $request->query('status');

        $scans = $exam->scans()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->with('student')
            ->latest()
            ->take(500)
            ->get();

        $scores = $scans->pluck('score')->map(fn ($score) => (float) $score);

        $stats = [
            'count' => $scans->count(),
            'review_count' => $exam->scans()->where('status', 'review')->count(),
            'average' => $scores->count() ? round($scores->avg(), 2) : 0,
            'max' => $scores->count() ? $scores->max() : 0,
            'min' => $scores->count() ? $scores->min() : 0,
        ];

        $itemAnalysis = $this->itemAnalysis($exam);

        return view('results.index', [
            'exam' => $exam,
            'scans' => $scans,
            'stats' => $stats,
            'itemAnalysis' => $itemAnalysis,
            'status' => $status,
        ]);
    }

    /**
     * @return array<int, array{attempts: int, correct: int, options: array<string, int>}>
     */
    private function itemAnalysis(Exam $exam): array
    {
        $keys = $exam->answerKeys()->get()->keyBy('booklet');
        $analysis = [];

        for ($question = 1; $question <= $exam->question_count; $question++) {
            $analysis[$question] = ['attempts' => 0, 'correct' => 0, 'options' => []];
        }

        $exam->scans()->chunk(200, function ($scans) use (&$analysis, $keys, $exam): void {
            foreach ($scans as $scan) {
                $key = $keys->get($scan->booklet);

                if (! $key) {
                    continue;
                }

                foreach (range(1, $exam->question_count) as $question) {
                    $expected = $key->answers[$question] ?? $key->answers[(string) $question] ?? null;
                    $mark = $scan->answers[$question] ?? $scan->answers[(string) $question] ?? null;

                    if ($mark === null || $mark === '' || is_array($mark)) {
                        continue;
                    }

                    $analysis[$question]['attempts']++;
                    $analysis[$question]['options'][$mark] = ($analysis[$question]['options'][$mark] ?? 0) + 1;

                    if ($expected !== null && strtoupper((string) $mark) === strtoupper((string) $expected)) {
                        $analysis[$question]['correct']++;
                    }
                }
            }
        });

        return $analysis;
    }
}
