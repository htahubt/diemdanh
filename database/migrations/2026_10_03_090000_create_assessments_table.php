<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_section_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('kind', 40);
            $table->decimal('max_score', 6, 2);
            $table->decimal('weight', 5, 2);
            $table->date('edit_until')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['course_section_id', 'title']);
        });

        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learner_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('not_entered')->index();
            $table->decimal('score', 6, 2)->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entered_at')->nullable();
            $table->timestamps();
            $table->unique(['assessment_id', 'learner_id']);
        });

        Schema::create('assessment_result_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('before_status', 20);
            $table->decimal('before_score', 6, 2)->nullable();
            $table->string('after_status', 20);
            $table->decimal('after_score', 6, 2)->nullable();
            $table->string('reason', 1000)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_result_changes');
        Schema::dropIfExists('assessment_results');
        Schema::dropIfExists('assessments');
    }
};
