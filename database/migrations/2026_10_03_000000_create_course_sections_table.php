<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->string('section_code', 40);
            $table->unsignedTinyInteger('semester');
            $table->string('academic_year', 9);
            $table->foreignId('lecturer_id')->nullable()->constrained('lecturers')->restrictOnDelete();
            $table->unsignedSmallInteger('capacity')->default(40);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['course_id', 'section_code', 'academic_year', 'semester'], 'course_sections_term_code_unique');
            $table->index(['academic_year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sections');
    }
};
