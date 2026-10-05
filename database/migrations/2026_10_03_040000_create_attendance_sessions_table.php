<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_section_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('session_number');
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 100)->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['course_section_id', 'session_number'], 'attendance_section_number_unique');
            $table->unique(['course_section_id', 'session_date'], 'attendance_section_date_unique');
            $table->index(['session_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
