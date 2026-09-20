<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(Request $request): View
    {
        $classes = SchoolClass::where('user_id', $request->user()->id)
            ->withCount('students')
            ->latest()
            ->get();

        return view('classes.index', ['classes' => $classes]);
    }

    public function create(): View
    {
        return view('classes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $class = SchoolClass::create(['user_id' => $request->user()->id] + $validated);

        return redirect()->route('classes.show', $class)->with('status', 'Sınıf oluşturuldu.');
    }

    public function show(Request $request, SchoolClass $class): View
    {
        abort_if($class->user_id !== $request->user()->id, 404);

        $class->load('students');

        return view('classes.show', ['class' => $class]);
    }

    public function edit(Request $request, SchoolClass $class): View
    {
        abort_if($class->user_id !== $request->user()->id, 404);

        return view('classes.edit', ['class' => $class]);
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        abort_if($class->user_id !== $request->user()->id, 404);

        $class->update($request->validate(['name' => ['required', 'string', 'max:255']]));

        return redirect()->route('classes.show', $class)->with('status', 'Sınıf güncellendi.');
    }

    public function destroy(Request $request, SchoolClass $class): RedirectResponse
    {
        abort_if($class->user_id !== $request->user()->id, 404);

        $class->delete();

        return redirect()->route('classes.index')->with('status', 'Sınıf silindi.');
    }
}
