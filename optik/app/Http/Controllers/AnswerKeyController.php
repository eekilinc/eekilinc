<?php

namespace App\Http\Controllers;

use App\Models\AnswerKey;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnswerKeyController extends Controller
{
    /**
     * @return array<string>
     */
    private function options(Exam $exam): array
    {
        return array_slice(['A', 'B', 'C', 'D', 'E'], 0, $exam->option_count);
    }

    public function edit(Request $request, Exam $exam, string $booklet): View
    {
        abort_if($exam->user_id !== $request->user()->id, 404);
        $booklet = strtoupper($booklet);
        abort_unless(in_array($booklet, $exam->booklets ?? [], true), 404);

        $key = AnswerKey::firstOrCreate(
            ['exam_id' => $exam->id, 'booklet' => $booklet],
            ['answers' => [], 'points' => null, 'cancelled' => []]
        );

        return view('keys.edit', [
            'exam' => $exam,
            'booklet' => $booklet,
            'key' => $key,
            'options' => $this->options($exam),
        ]);
    }

    public function update(Request $request, Exam $exam, string $booklet): RedirectResponse
    {
        abort_if($exam->user_id !== $request->user()->id, 404);
        $booklet = strtoupper($booklet);
        abort_unless(in_array($booklet, $exam->booklets ?? [], true), 404);

        $options = $this->options($exam);
        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'in:'.implode(',', $options)],
            'cancelled' => ['nullable', 'array'],
            'cancelled.*' => ['integer', 'min:1', 'max:'.$exam->question_count],
        ]);

        $answers = [];
        foreach ($validated['answers'] ?? [] as $question => $mark) {
            if ($mark !== null && $mark !== '') {
                $answers[(string) $question] = $mark;
            }
        }

        AnswerKey::updateOrCreate(
            ['exam_id' => $exam->id, 'booklet' => $booklet],
            ['answers' => $answers, 'cancelled' => array_values($validated['cancelled'] ?? [])]
        );

        return redirect()->route('exams.show', $exam)->with('status', "{$booklet} kitapçığı anahtarı kaydedildi.");
    }
}
