<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\FormTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormTemplate>
 */
class FormTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'version' => 1,
            'spec' => ['spec' => 'optik-form/v1'],
            'pdf_path' => null,
        ];
    }
}
