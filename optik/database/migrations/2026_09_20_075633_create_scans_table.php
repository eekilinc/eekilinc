<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_no_raw', 20);
            $table->char('booklet', 1);
            $table->json('answers');
            $table->decimal('score', 6, 2)->default(0);
            $table->decimal('max_score', 6, 2)->default(0);
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->enum('status', ['ok', 'review', 'duplicate'])->default('ok');
            $table->string('paper_image_path', 512)->nullable();
            $table->foreignId('scanned_by')->constrained('users')->cascadeOnDelete();
            $table->string('device_id', 64)->nullable();
            $table->timestamps();

            $table->index(['exam_id', 'student_no_raw']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
