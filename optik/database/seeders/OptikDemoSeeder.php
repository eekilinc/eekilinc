<?php

namespace Database\Seeders;

use App\Models\AnswerKey;
use App\Models\Exam;
use App\Models\FormTemplate;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo verisi: ilk girişte test edilebilen öğretmen + sınıf + yayınlanmış sınav.
 * Tekrar çalıştırılabilir (firstOrCreate/updateOrCreate).
 */
class OptikDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'ogretmen@optik.test'],
            [
                'name' => 'Demo Öğretmen',
                'password' => Hash::make('optik1234'),
                'email_verified_at' => now(),
            ]
        );

        $class = SchoolClass::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Deneme Sınıfı 10-A'],
        );

        foreach ([
            ['1001', 'Ali', 'Yılmaz'],
            ['1002', 'Zeynep', 'Kaya'],
            ['1003', 'Mehmet', 'Demir'],
            ['1004', 'Elif', 'Şahin'],
            ['1005', 'Emre', 'Çelik'],
        ] as [$studentNo, $firstName, $lastName]) {
            Student::updateOrCreate(
                ['class_id' => $class->id, 'student_no' => $studentNo],
                ['first_name' => $firstName, 'last_name' => $lastName],
            );
        }

        $exam = Exam::updateOrCreate(
            ['user_id' => $user->id, 'title' => 'Matematik Deneme 1'],
            [
                'class_id' => $class->id,
                'course' => 'Matematik',
                'question_count' => 10,
                'option_count' => 5,
                'booklets' => ['A', 'B'],
                'status' => 'published',
            ],
        );

        FormTemplate::firstOrCreate(
            ['exam_id' => $exam->id, 'version' => 1],
            ['spec' => json_decode(file_get_contents(resource_path('form-spec/v1.json')), true)],
        );

        AnswerKey::updateOrCreate(
            ['exam_id' => $exam->id, 'booklet' => 'A'],
            ['answers' => [
                '1' => 'B', '2' => 'C', '3' => 'A', '4' => 'D', '5' => 'B',
                '6' => 'E', '7' => 'A', '8' => 'C', '9' => 'D', '10' => 'B',
            ], 'cancelled' => []],
        );

        AnswerKey::updateOrCreate(
            ['exam_id' => $exam->id, 'booklet' => 'B'],
            ['answers' => [
                '1' => 'C', '2' => 'A', '3' => 'D', '4' => 'B', '5' => 'E',
                '6' => 'B', '7' => 'C', '8' => 'A', '9' => 'B', '10' => 'D',
            ], 'cancelled' => []],
        );
    }
}
