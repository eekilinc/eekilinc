<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    private function ownClass(Request $request, SchoolClass $class): void
    {
        abort_if($class->user_id !== $request->user()->id, 404);
    }

    public function create(Request $request, SchoolClass $class): View
    {
        $this->ownClass($request, $class);

        return view('students.create', ['class' => $class]);
    }

    public function store(Request $request, SchoolClass $class): RedirectResponse
    {
        $this->ownClass($request, $class);

        $validated = $request->validate([
            'student_no' => ['required', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
        ]);

        $class->students()->updateOrCreate(
            ['student_no' => $validated['student_no']],
            ['first_name' => $validated['first_name'], 'last_name' => $validated['last_name']]
        );

        return redirect()->route('classes.show', $class)->with('status', 'Öğrenci kaydedildi.');
    }

    public function edit(Request $request, SchoolClass $class, Student $student): View
    {
        $this->ownClass($request, $class);
        abort_if($student->class_id !== $class->id, 404);

        return view('students.edit', ['class' => $class, 'student' => $student]);
    }

    public function update(Request $request, SchoolClass $class, Student $student): RedirectResponse
    {
        $this->ownClass($request, $class);
        abort_if($student->class_id !== $class->id, 404);

        $student->update($request->validate([
            'student_no' => ['required', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
        ]));

        return redirect()->route('classes.show', $class)->with('status', 'Öğrenci güncellendi.');
    }

    public function destroy(Request $request, SchoolClass $class, Student $student): RedirectResponse
    {
        $this->ownClass($request, $class);
        abort_if($student->class_id !== $class->id, 404);

        $student->delete();

        return redirect()->route('classes.show', $class)->with('status', 'Öğrenci silindi.');
    }
}
