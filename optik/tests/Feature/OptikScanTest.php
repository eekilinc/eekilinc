<?php

namespace Tests\Feature;

use App\Models\AnswerKey;
use App\Models\Exam;
use App\Models\FormTemplate;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\ScanScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptikScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_returns_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'test-cihaz',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    public function test_scan_scores_correctly_and_returns_ok(): void
    {
        [$user, $exam] = $this->seedExam();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/scans', [
            'exam_id' => $exam->id,
            'booklet' => 'A',
            'student_no' => '20240137',
            'answers' => ['1' => 'B', '2' => 'C', '3' => 'A', '4' => 'D', '5' => 'B'],
            'confidence' => 95,
            'device_id' => 'test-cihaz',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.score', 100)
            ->assertJsonPath('data.max_score', 100)
            ->assertJsonPath('data.status', 'ok');
    }

    public function test_duplicate_scan_returns_conflict(): void
    {
        [$user, $exam] = $this->seedExam();
        $payload = [
            'exam_id' => $exam->id,
            'booklet' => 'A',
            'student_no' => '20240137',
            'answers' => ['1' => 'B', '2' => 'C', '3' => 'A', '4' => 'D', '5' => 'B'],
        ];

        $this->actingAs($user, 'sanctum')->postJson('/api/scans', $payload)->assertCreated();
        $this->actingAs($user, 'sanctum')->postJson('/api/scans', $payload)->assertConflict();
    }

    public function test_multi_mark_scan_needs_review(): void
    {
        [$user, $exam] = $this->seedExam();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/scans', [
            'exam_id' => $exam->id,
            'booklet' => 'A',
            'student_no' => '20240137',
            'answers' => ['1' => 'B', '2' => ['A', 'C'], '3' => 'A', '4' => 'D', '5' => 'B'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'review')
            ->assertJsonPath('data.review_items', [2]);
    }

    public function test_stale_qr_version_returns_gone(): void
    {
        [$user, $exam] = $this->seedExam();
        $scoring = app(ScanScoringService::class);
        $staleQr = "OPTIK1:{$exam->id}:1:A:".$scoring->qrChecksum($exam->id, 1, 'A');

        FormTemplate::factory()->create(['exam_id' => $exam->id, 'version' => 2]);

        $this->actingAs($user, 'sanctum')->postJson('/api/scans', [
            'exam_id' => $exam->id,
            'booklet' => 'A',
            'student_no' => '20240137',
            'answers' => ['1' => 'B'],
            'qr_payload' => $staleQr,
        ])->assertStatus(410);
    }

    /**
     * @return array{0: User, 1: Exam}
     */
    private function seedExam(): array
    {
        $user = User::factory()->create();
        $class = SchoolClass::factory()->create(['user_id' => $user->id]);
        Student::factory()->create(['class_id' => $class->id, 'student_no' => '20240137']);

        $exam = Exam::factory()->create(['user_id' => $user->id, 'class_id' => $class->id]);
        FormTemplate::factory()->create(['exam_id' => $exam->id, 'version' => 1]);
        AnswerKey::factory()->create(['exam_id' => $exam->id, 'booklet' => 'A']);

        return [$user, $exam];
    }
}
