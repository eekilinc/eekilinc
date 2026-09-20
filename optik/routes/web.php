<?php

use App\Http\Controllers\AnswerKeyController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\FormPdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentImportController;
use App\Models\Exam;
use App\Models\Scan;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::bind('class', fn (string $value) => SchoolClass::where('id', $value)->firstOrFail());

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('landing');
})->name('home');

Route::get('/dashboard', function (Request $request) {
    $userId = $request->user()->id;

    return view('dashboard', [
        'classCount' => SchoolClass::where('user_id', $userId)->count(),
        'examCount' => Exam::where('user_id', $userId)->count(),
        'reviewCount' => Scan::whereHas('exam', fn ($query) => $query->where('user_id', $userId))->where('status', 'review')->count(),
        'recentExams' => Exam::where('user_id', $userId)->withCount('scans')->latest()->take(5)->get(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('classes', ClassController::class);
    Route::resource('classes.students', StudentController::class)->except(['index', 'show']);
    Route::post('classes/{class}/students/import', StudentImportController::class)->name('classes.students.import');

    Route::resource('exams', ExamController::class);
    Route::get('exams/{exam}/keys/{booklet}/edit', [AnswerKeyController::class, 'edit'])->name('exams.keys.edit');
    Route::put('exams/{exam}/keys/{booklet}', [AnswerKeyController::class, 'update'])->name('exams.keys.update');
    Route::get('exams/{exam}/form.pdf', FormPdfController::class)->name('exams.form-pdf');
    Route::delete('exams/{exam}/form-template', [ExamController::class, 'destroyFormTemplate'])->name('exams.form-template.destroy');
    Route::get('exams/{exam}/results', [ExamResultController::class, 'index'])->name('results.index');

    Route::get('scans/{scan}/edit', [ScanController::class, 'edit'])->name('scans.edit');
    Route::put('scans/{scan}', [ScanController::class, 'update'])->name('scans.update');
});

require __DIR__.'/auth.php';
