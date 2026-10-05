<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturers', function (Blueprint $table) {
            $table->id();
            $table->string('lecturer_code', 30)->unique();
            $table->string('name', 160);
            $table->string('academic_title', 120)->nullable();
            $table->string('department', 160)->nullable()->index();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturers');
    }
};