<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('lecturer_id')->nullable()->unique()->constrained()->nullOnDelete();
        });

        foreach (DB::table('users')->where('role', 'lecturer')->whereNull('lecturer_id')->get(['id', 'email']) as $user) {
            $lecturerId = DB::table('lecturers')->where('email', $user->email)->value('id');
            if ($lecturerId && ! DB::table('users')->where('lecturer_id', $lecturerId)->exists()) {
                DB::table('users')->where('id', $user->id)->update(['lecturer_id' => $lecturerId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lecturer_id');
        });
    }
};