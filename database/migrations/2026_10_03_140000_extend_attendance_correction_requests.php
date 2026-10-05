<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_correction_requests', function (Blueprint $table) {
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requester_type', 20)->default('learner');
        });
    }

    public function down(): void
    {
        Schema::table('student_correction_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by');
            $table->dropColumn('requester_type');
        });
    }
};
