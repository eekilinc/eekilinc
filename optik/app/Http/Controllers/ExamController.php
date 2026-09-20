<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $exams = Exam::where('user_id', $request->user()->id)
            ->withCount('scans')
            ->latest()
            ->get();

        return view('exams.index', ['exams' => $exams]);
    }

    public function create(Request $request): View
    {
        $classes = SchoolClass::where('user_id', $request->user()->id)->get();

        return view('exams.create', ['classes' => $classes]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateExam($request);

        $exam = Exam::create(['user_id' => $request->user()->id] + $validated);

        return redirect()->route('exams.show', $exam)->with('status', 'Sınav oluşturuldu. Şimdi cevap anahtarını girin.');
    }

    public function show(Request $request, Exam $exam): View
    {
        abort_if($exam->user_id !== $request->user()->id, 404);

        $exam->load(['answerKeys', 'formTemplates' => fn ($query) => $query->latest('version')]);

        return view('exams.show', ['exam' => $exam]);
    }

    public function destroyFormTemplate(Request $request, Exam $exam): RedirectResponse
    {
        abort_if($exam->user_id !== $request->user()->id, 404);

        $exam->formTemplates()->delete();

        return redirect()->route('exams.show', $exam)->with('status', 'Form şablonu silindi. Yeni PDF yazdırın.');
    }

    public function edit(Request $request, Exam $exam): View
    {
        abort_if($exam->user_id !== $request->user()->id, 404);

        $classes = SchoolClass::where('user_id', $request->user()->id)->get();

        return view('exams.edit', ['exam' => $exam, 'classes' => $classes]);
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        abort_if($exam->user_id !== $request->user()->id, 404);

        $exam->update($this->validateExam($request));

        return redirect()->route('exams.show', $exam)->with('status', 'Sınav güncellendi.');
    }

    public function destroy(Request $request, Exam $exam): RedirectResponse
    {
        abort_if($exam->user_id !== $request->user()->id, 404);

        $exam->delete();

        return redirect()->route('exams.index')->with('status', 'Sınav silindi.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateExam(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'class_id' => ['nullable', 'integer'],
            'question_count' => ['required', 'integer', 'min:5', 'max:100'],
            'option_count' => ['required', 'integer', 'min:4', 'max:5'],
            'booklets' => ['required', 'array', 'min:1'],
            'booklets.*' => ['in:A,B,C,D'],
            'status' => ['sometimes', 'in:draft,published,archived'],
        ]);

        if (! empty($validated['class_id'])) {
            $class = SchoolClass::find($validated['class_id']);
            abort_if(! $class || $class->user_id !== $request->user()->id, 422, 'Geçersiz sınıf.');
        }

        $validated['booklets'] = array_values(array_unique($validated['booklets']));

        return $validated;
    }
}
