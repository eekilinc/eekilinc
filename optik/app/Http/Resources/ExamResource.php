<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'course' => $this->course,
            'question_count' => $this->question_count,
            'option_count' => $this->option_count,
            'booklets' => $this->booklets,
            'status' => $this->status,
        ];

        if ($this->relationLoaded('formTemplates')) {
            $form = $this->formTemplates->first();
            $data['form_version'] = $form?->version;
            $data['form_spec'] = $form?->spec;
        }

        return $data;
    }
}
