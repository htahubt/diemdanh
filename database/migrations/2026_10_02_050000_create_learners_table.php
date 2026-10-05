<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learners', function (Blueprint $table) {
            $table->id();
            $table->string('student_code', 40)->unique();
            $table->string('full_name', 160);
            $table->date('date_of_birth')->nullable();
            $table->string('cohort', 20)->nullable();
            $table->string('major', 160)->nullable();
            $table->string('class_name', 80)->nullable();
            $table->string('study_status', 30)->default('active')->index();
            $table->string('phone', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->text('enrolled_courses')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learners');
    }
};
