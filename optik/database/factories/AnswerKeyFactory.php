<?php

namespace Database\Factories;

use App\Models\AnswerKey;
use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnswerKey>
 */
class AnswerKeyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'booklet' => 'A',
            'answers' => ['1' => 'B', '2' => 'C', '3' => 'A', '4' => 'D', '5' => 'B'],
            'points' => null,
            'cancelled' => [],
        ];
    }
}
