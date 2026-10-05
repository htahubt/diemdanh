<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('requester_type', 20);
            $table->string('original_status', 20);
            $table->decimal('original_score', 6, 2)->nullable();
            $table->string('requested_status', 20);
            $table->decimal('requested_score', 6, 2)->nullable();
            $table->string('reason', 1000);
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 1000)->nullable();
            $table->timestamps();
            $table->index(['assessment_result_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_correction_requests');
    }
};
