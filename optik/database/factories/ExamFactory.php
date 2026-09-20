<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'class_id' => null,
            'title' => fake()->sentence(3),
            'course' => fake()->word(),
            'question_count' => 5,
            'option_count' => 4,
            'booklets' => ['A', 'B'],
            'status' => 'published',
        ];
    }
}
