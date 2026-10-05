<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_sections', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('training_format', 80)->nullable();
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('room', 100)->nullable();
            $table->unsignedSmallInteger('expected_sessions')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('course_sections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn([
                'training_format', 'weekday', 'start_time', 'end_time', 'room',
                'expected_sessions', 'starts_on', 'ends_on',
            ]);
        });
    }
};
