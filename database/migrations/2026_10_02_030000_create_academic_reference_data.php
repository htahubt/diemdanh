<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 160)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('academic_options', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('name', 120);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['type', 'name']);
        });

        Schema::table('lecturers', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('academic_rank_id')->nullable()->constrained('academic_options')->restrictOnDelete();
            $table->foreignId('academic_degree_id')->nullable()->constrained('academic_options')->restrictOnDelete();
        });

        $rankDefaults = ['Giáo sư', 'Phó Giáo sư', 'Không có học hàm'];
        $degreeDefaults = ['Tiến sĩ', 'Thạc sĩ', 'Cử nhân', 'Kỹ sư', 'Khác'];

        foreach (DB::table('lecturers')->get(['academic_rank', 'academic_title'])->all() as $lecturer) {
            $rankDefaults[] = trim((string) $lecturer->academic_rank) ?: 'Không có học hàm';
            $degreeDefaults[] = trim((string) $lecturer->academic_title) ?: 'Chưa phân loại';
        }

        foreach (array_unique($rankDefaults) as $name) {
            DB::table('academic_options')->insertOrIgnore([
                'type' => 'rank',
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (array_unique($degreeDefaults) as $name) {
            DB::table('academic_options')->insertOrIgnore([
                'type' => 'degree',
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $departmentNames = DB::table('lecturers')->whereNotNull('department')->distinct()->pluck('department');
        $nextDepartmentCode = 1;

        foreach ($departmentNames as $name) {
            $slug = Str::upper(Str::slug($name));
            $code = 'DV-' . ($slug !== '' ? $slug : str_pad((string) $nextDepartmentCode, 4, '0', STR_PAD_LEFT));
            $existingCode = DB::table('departments')->where('code', $code)->exists();

            if ($existingCode) {
                $code .= '-' . $nextDepartmentCode;
            }

            DB::table('departments')->insert([
                'code' => $code,
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $nextDepartmentCode++;
        }

        foreach (DB::table('lecturers')->get(['id', 'department', 'academic_rank', 'academic_title'])->all() as $lecturer) {
            $departmentId = $lecturer->department
                ? DB::table('departments')->where('name', $lecturer->department)->value('id')
                : null;
            $rankName = trim((string) $lecturer->academic_rank) ?: 'Không có học hàm';
            $degreeName = trim((string) $lecturer->academic_title) ?: 'Chưa phân loại';
            $rankId = DB::table('academic_options')->where('type', 'rank')->where('name', $rankName)->value('id');
            $degreeId = DB::table('academic_options')->where('type', 'degree')->where('name', $degreeName)->value('id');

            DB::table('lecturers')->where('id', $lecturer->id)->update([
                'department_id' => $departmentId,
                'academic_rank_id' => $rankId,
                'academic_degree_id' => $degreeId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('academic_rank_id');
            $table->dropConstrainedForeignId('academic_degree_id');
        });

        Schema::dropIfExists('academic_options');
        Schema::dropIfExists('departments');
    }
};