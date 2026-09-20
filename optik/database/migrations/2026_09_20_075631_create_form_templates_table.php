<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->json('spec');
            $table->string('pdf_path', 512)->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_templates');
    }
};
