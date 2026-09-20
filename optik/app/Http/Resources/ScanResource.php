<?php

namespace App\Http\Resources;

use App\Services\ScanScoringService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ScanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $reviewItems = [];

        if ($this->relationLoaded('exam') && $this->exam) {
            $key = $this->exam->answerKeys->firstWhere('booklet', $this->booklet);

            if ($key) {
                $reviewItems = app(ScanScoringService::class)->reviewItems($this->exam, $key, $this->answers ?? []);
            }
        }

        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'student_id' => $this->student_id,
            'student_no' => $this->student_no_raw,
            'booklet' => $this->booklet,
            'score' => (float) $this->score,
            'max_score' => (float) $this->max_score,
            'status' => $this->status,
            'confidence' => $this->confidence,
            'review_items' => $reviewItems,
            'paper_image_url' => $this->paper_image_path ? Storage::url($this->paper_image_path) : null,
            'created_at' => $this->created_at,
        ];
    }
}
