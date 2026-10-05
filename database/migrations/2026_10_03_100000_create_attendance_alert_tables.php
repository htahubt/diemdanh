<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_alert_settings')) {
            Schema::create('attendance_alert_settings', function (Blueprint $table) {
                $table->id();
                $table->string('rule_key', 80)->unique();
                $table->string('label', 180);
                $table->decimal('threshold', 8, 2)->nullable();
                $table->string('unit', 30)->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('attendance_alerts')) {
            Schema::create('attendance_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('fingerprint', 120)->unique();
                $table->string('alert_type', 60)->index();
                $table->string('severity', 20)->default('warning');
                $table->string('title', 180);
                $table->text('description');
                $table->foreignId('learner_id')->nullable()->constrained()->nullOnDelete();
                $table->string('learner_code', 40)->nullable()->index();
                $table->foreignId('course_section_id')->nullable()->constrained()->nullOnDelete();
                $table->string('section_code', 40)->nullable()->index();
                $table->foreignId('attendance_session_id')->nullable()->constrained()->nullOnDelete();
                $table->json('details')->nullable();
                $table->string('status', 20)->default('open')->index();
                $table->timestamp('first_detected_at')->useCurrent();
                $table->timestamp('last_detected_at')->useCurrent();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_note')->nullable();
                $table->timestamps();
                $table->index(['status', 'severity', 'last_detected_at']);
            });
        }

        if (! Schema::hasTable('attendance_alert_histories')) {
            Schema::create('attendance_alert_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attendance_alert_id')->constrained()->cascadeOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('before_status', 20)->nullable();
                $table->string('after_status', 20);
                $table->text('action_note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_alert_histories');
        Schema::dropIfExists('attendance_alerts');
        Schema::dropIfExists('attendance_alert_settings');
    }
};
