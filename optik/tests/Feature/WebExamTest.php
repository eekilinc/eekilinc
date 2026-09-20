<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebExamTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_exams(): void
    {
        $this->get('/exams')->assertRedirect('/login');
    }

    public function test_user_can_create_exam_and_answer_key_and_print_pdf(): void
    {
        $user = User::factory()->create();
        $class = SchoolClass::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post('/exams', [
            'title' => 'Mat Deneme 1',
            'course' => 'Matematik',
            'class_id' => $class->id,
            'question_count' => 5,
            'option_count' => 4,
            'booklets' => ['A', 'B'],
        ]);
        $response->assertRedirect();

        $exam = Exam::first();
        $this->assertSame('Mat Deneme 1', $exam->title);

        $this->actingAs($user)->get("/exams/{$exam->id}")->assertOk();

        $this->actingAs($user)->put("/exams/{$exam->id}/keys/A/edit", [])->assertStatus(405);
        $this->actingAs($user)->get("/exams/{$exam->id}/keys/A/edit")->assertOk();

        $this->actingAs($user)->put("/exams/{$exam->id}/keys/A", [
            'answers' => ['1' => 'B', '2' => 'C', '3' => 'A', '4' => 'D', '5' => 'B'],
        ])->assertRedirect("/exams/{$exam->id}");

        $this->assertSame('B', $exam->fresh()->answerKeys()->where('booklet', 'A')->first()->answers['1']);

        $this->actingAs($user)->get("/exams/{$exam->id}/form.pdf?booklet=A")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)->get("/exams/{$exam->id}/results")->assertOk();
    }

    public function test_guest_sees_landing_page(): void
    {
        $this->get('/')->assertOk()->assertSee('saniyeler içinde okutun');
    }

    public function test_authenticated_user_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect('/dashboard');
    }

    public function test_user_cannot_view_other_users_exam(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $exam = Exam::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)->get("/exams/{$exam->id}")->assertNotFound();
    }
}
