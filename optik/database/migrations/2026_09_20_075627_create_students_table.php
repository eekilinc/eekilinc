<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->string('student_no', 20);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->timestamps();

            $table->unique(['class_id', 'student_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
