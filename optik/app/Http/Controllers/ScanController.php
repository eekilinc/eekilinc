<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Services\ScanScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function edit(Request $request, Scan $scan): View
    {
        abort_if($scan->exam->user_id !== $request->user()->id, 404);

        $scan->load('exam.answerKeys');

        return view('scans.edit', [
            'scan' => $scan,
            'options' => array_slice(['A', 'B', 'C', 'D', 'E'], 0, $scan->exam->option_count),
        ]);
    }

    public function update(Request $request, Scan $scan, ScanScoringService $scoring): RedirectResponse
    {
        abort_if($scan->exam->user_id !== $request->user()->id, 404);

        $options = array_slice(['A', 'B', 'C', 'D', 'E'], 0, $scan->exam->option_count);
        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'in:'.implode(',', $options)],
        ]);

        $answers = [];
        foreach ($validated['answers'] ?? [] as $question => $mark) {
            if ($mark !== null && $mark !== '') {
                $answers[(string) $question] = $mark;
            }
        }

        $key = $scan->exam->answerKeys()->where('booklet', $scan->booklet)->first();
        abort_unless($key, 422, 'Bu kitapçık için cevap anahtarı yok.');

        $result = $scoring->score($scan->exam, $key, $answers);

        $scan->update([
            'answers' => $answers,
            'score' => $result['score'],
            'max_score' => $result['max_score'],
            'status' => $result['review_items'] === [] ? 'ok' : 'review',
        ]);

        return redirect()->route('results.index', $scan->exam)->with('status', 'Kağıt düzeltildi ve yeniden puanlandı.');
    }
}
