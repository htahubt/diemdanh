<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_section_learner', function (Blueprint $table) {
            $table->date('effective_from')->nullable()->index();
            $table->boolean('is_new')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('course_section_learner', function (Blueprint $table) {
            $table->dropColumn(['effective_from', 'is_new']);
        });
    }
};
