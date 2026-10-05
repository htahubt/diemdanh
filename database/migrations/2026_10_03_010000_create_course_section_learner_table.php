<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_section_learner', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learner_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['course_section_id', 'learner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_section_learner');
    }
};
