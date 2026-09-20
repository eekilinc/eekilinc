<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answer_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->char('booklet', 1);
            $table->json('answers');
            $table->json('points')->nullable();
            $table->json('cancelled')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'booklet']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_keys');
    }
};
