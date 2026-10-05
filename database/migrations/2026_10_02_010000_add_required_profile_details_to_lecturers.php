<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lecturers', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('academic_rank', 120)->nullable();
            $table->string('specialization', 160)->nullable();
            $table->string('position', 160)->nullable();
            $table->text('national_id')->nullable();
            $table->string('national_id_hash', 64)->nullable()->unique();
            $table->date('id_issued_at')->nullable();
            $table->string('id_issued_place', 160)->nullable();
            $table->text('permanent_address')->nullable();
            $table->text('contact_address')->nullable();
            $table->text('bank_account_number')->nullable();
            $table->string('bank_name', 160)->nullable();
            $table->string('bank_branch', 160)->nullable();
            $table->string('bank_account_name', 160)->nullable();
            $table->string('profile_status', 30)->default('submitted')->index();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('identity_verified_at')->nullable();
            $table->timestamp('bank_verified_at')->nullable();
        });

        Schema::create('lecturer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecturer_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 60);
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lecturer_profile_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecturer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->longText('changes')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_profile_histories');
        Schema::dropIfExists('lecturer_documents');

        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropUnique('lecturers_national_id_hash_unique');
            $table->dropIndex('lecturers_profile_status_index');
            $table->dropColumn([
                'date_of_birth', 'gender', 'academic_rank', 'specialization', 'position',
                'national_id', 'national_id_hash', 'id_issued_at', 'id_issued_place',
                'permanent_address', 'contact_address', 'bank_account_number', 'bank_name',
                'bank_branch', 'bank_account_name', 'profile_status', 'review_note',
                'submitted_at', 'approved_at', 'identity_verified_at', 'bank_verified_at',
            ]);
        });
    }
};