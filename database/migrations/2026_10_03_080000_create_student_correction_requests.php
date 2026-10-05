<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_record_id')->constrained()->cascadeOnDelete();
            $table->string('original_status', 20);
            $table->string('requested_status', 20);
            $table->string('reason', 1000);
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 1000)->nullable();
            $table->timestamps();
            $table->index(['learner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_correction_requests');
    }
};
