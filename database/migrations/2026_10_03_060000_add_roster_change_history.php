<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_section_learner', function (Blueprint $table) {
            $table->date('effective_until')->nullable()->index();
        });

        Schema::create('roster_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('section_code', 40);
            $table->foreignId('learner_id')->nullable()->constrained()->nullOnDelete();
            $table->string('learner_code', 40);
            $table->string('action', 30);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->index(['course_section_id', 'created_at']);
            $table->index(['learner_code', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_change_logs');

        Schema::table('course_section_learner', function (Blueprint $table) {
            $table->dropColumn('effective_until');
        });
    }
};
