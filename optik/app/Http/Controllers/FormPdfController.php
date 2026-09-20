<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\FormTemplate;
use App\Services\ScanScoringService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

class FormPdfController extends Controller
{
    public function __invoke(Request $request, Exam $exam, ScanScoringService $scoring): Response
    {
        abort_if($exam->user_id !== $request->user()->id, 404);

        $booklet = strtoupper($request->query('booklet', $exam->booklets[0] ?? 'A'));
        abort_unless(in_array($booklet, $exam->booklets ?? [], true), 404);

        $spec = $this->generateFormSpec($exam);
        $template = FormTemplate::updateOrCreate(
            ['exam_id' => $exam->id, 'version' => 1],
            ['spec' => $spec]
        );

        $payload = "OPTIK1:{$exam->id}:{$template->version}:{$booklet}:".$scoring->qrChecksum($exam->id, $template->version, $booklet);
        $qrBase64 = base64_encode(QrCode::format('png')->size(120)->generate($payload));

        $pdf = Pdf::loadView('pdf.form', [
            'exam' => $exam,
            'booklet' => $booklet,
            'version' => $template->version,
            'qrBase64' => $qrBase64,
            'options' => array_slice(['A', 'B', 'C', 'D', 'E'], 0, $exam->option_count),
            'columns' => $spec['questions']['layout']['columns'],
            'rowsPerColumn' => $spec['questions']['layout']['rows_per_column'],
        ])->setPaper('a4');

        return $pdf->download("optik-sinav-{$exam->id}-{$booklet}.pdf");
    }

    private function generateFormSpec(Exam $exam): array
    {
        $baseSpec = json_decode(file_get_contents(resource_path('form-spec/v1.json')), true);

        $columns = match (true) {
            $exam->question_count <= 40 => 2,
            $exam->question_count <= 75 => 3,
            default => 4,
        };
        $rowsPerColumn = (int) ceil($exam->question_count / $columns);

        $baseSpec['defaults']['question_count'] = $exam->question_count;
        $baseSpec['defaults']['option_count'] = $exam->option_count;
        $baseSpec['defaults']['options'] = array_slice(['A', 'B', 'C', 'D', 'E'], 0, $exam->option_count);
        $baseSpec['student_no']['digits'] = 8;
        $baseSpec['booklet']['choices'] = $exam->booklets ?? ['A', 'B', 'C', 'D'];
        $baseSpec['questions']['layout']['columns'] = $columns;
        $baseSpec['questions']['layout']['rows_per_column'] = $rowsPerColumn;

        return $baseSpec;
    }
}
