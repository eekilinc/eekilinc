<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExamController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $exams = Exam::where('user_id', $request->user()->id)
            ->where('status', 'published')
            ->latest()
            ->get();

        return ExamResource::collection($exams);
    }

    public function show(Request $request, Exam $exam): ExamResource
    {
        abort_if($exam->user_id !== $request->user()->id || $exam->status !== 'published', 404);

        $exam->load(['formTemplates' => fn ($query) => $query->latest('version')]);

        return new ExamResource($exam);
    }
}
