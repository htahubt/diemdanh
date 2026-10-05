<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Learner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LearnerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeTrainingOffice();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'paused', 'inactive', 'graduated', 'dropped'])],
        ]);

        $learners = Learner::query()
            ->with(['department', 'courseSections'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query
                    ->where('student_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('major', 'like', "%{$search}%")
                    ->orWhere('class_name', 'like', "%{$search}%")
                    ->orWhere('cohort', 'like', "%{$search}%")
                    ->orWhereHas('department', fn (Builder $departmentQuery) => $departmentQuery->where('name', 'like', "%{$search}%")));
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('study_status', $status))
            ->orderBy('student_code')
            ->paginate(15)
            ->withQueryString();

        return view('work.learners.index', [
            'learners' => $learners,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'activeCount' => Learner::where('study_status', 'active')->count(),
            'inactiveCount' => Learner::where('study_status', '!=', 'active')->count(),
            'warningCount' => $learners->getCollection()->filter(fn (Learner $learner): bool => count($learner->dataWarnings()) > 0)->count(),
        ]);
    }

    public function downloadTemplate(): mixed
    {
        $this->authorizeTrainingOffice();
        $headers = [
            'student_code', 'full_name', 'date_of_birth', 'cohort', 'major',
            'class_name', 'study_status', 'phone', 'email', 'department_code',
        ];

        return response()->streamDownload(function () use ($headers): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);
            fclose($output);
        }, 'mau-nguoi-hoc.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $handle = fopen($validated['file']->getRealPath(), 'r');
        $header = $handle === false ? false : fgetcsv($handle);

        if ($header === false) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            return back()->withErrors(['import' => 'Không đọc được tệp CSV.']);
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $header = array_map(fn ($column): string => strtolower(trim((string) $column)), $header);
        $requiredHeaders = ['student_code', 'full_name'];
        $allowedHeaders = [
            'student_code', 'full_name', 'date_of_birth', 'cohort', 'major', 'class_name',
            'study_status', 'phone', 'email', 'department_code',
        ];

        if (array_diff($requiredHeaders, $header) || count($header) !== count(array_unique($header)) || array_diff($header, $allowedHeaders)) {
            fclose($handle);

            return back()->withErrors(['import' => 'Tiêu đề cột không đúng mẫu. Tải mẫu CSV chuẩn và giữ nguyên tên cột.']);
        }

        $rows = [];
        $rowErrors = [];
        $rowNumber = 1;
        while (($record = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (count(array_filter($record, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                continue;
            }

            if (count($record) > count($header)) {
                $rowErrors[] = "Dòng {$rowNumber}: số cột nhiều hơn tiêu đề mẫu.";
                continue;
            }

            if (count($rows) >= 2000) {
                $rowErrors[] = 'Tệp vượt quá giới hạn 2.000 hồ sơ.';
                break;
            }

            $record = array_pad($record, count($header), null);
            $row = array_combine($header, array_slice($record, 0, count($header)));
            $row = array_map(fn ($value) => is_string($value) && trim($value) === '' ? null : (is_string($value) ? trim($value) : $value), $row);
            $row['student_code'] = strtoupper((string) ($row['student_code'] ?? ''));
            $row['study_status'] = $row['study_status'] ?? 'active';
            $departmentCode = $row['department_code'] ?? null;
            unset($row['department_code']);
            $row['department_id'] = $departmentCode
                ? Department::whereRaw('UPPER(code) = ?', [strtoupper($departmentCode)])->value('id')
                : null;

            if ($departmentCode && ! $row['department_id']) {
                $rowErrors[] = "Dòng {$rowNumber}: không tìm thấy mã khoa / đơn vị {$departmentCode}.";
            }

            $validator = Validator::make($row, $this->rules(checkUnique: false));
            foreach ($validator->errors()->all() as $error) {
                $rowErrors[] = "Dòng {$rowNumber}: {$error}";
            }

            $rows[] = ['row_number' => $rowNumber, 'data' => $row];
        }
        fclose($handle);

        if (! $rows && ! $rowErrors) {
            $rowErrors[] = 'Tệp không có hồ sơ nào để nhập.';
        }

        $seenCodes = [];
        foreach ($rows as $row) {
            $code = $row['data']['student_code'];
            if (isset($seenCodes[$code])) {
                $rowErrors[] = "Dòng {$row['row_number']}: mã {$code} bị lặp trong tệp.";
            } else {
                $seenCodes[$code] = true;
            }

            if ($code !== '' && Learner::whereRaw('UPPER(student_code) = ?', [$code])->exists()) {
                $rowErrors[] = "Dòng {$row['row_number']}: mã {$code} đã tồn tại trong hệ thống.";
            }
        }

        if ($rowErrors) {
            return back()
                ->withErrors(['import' => 'Tệp chưa được nhập. Hãy sửa các dòng được báo lỗi rồi tải lên lại.'])
                ->with('import_errors', array_slice($rowErrors, 0, 40));
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                Learner::create($row['data'] + ['is_active' => true]);
            }
        });

        return redirect()->route('learners.index')->with('status', 'Đã nhập ' . count($rows) . ' hồ sơ người học.');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        $request->merge(['student_code' => strtoupper(trim((string) $request->input('student_code')))]);
        $validated = $request->validate($this->rules());

        $validated['student_code'] = strtoupper(trim($validated['student_code']));
        $validated['full_name'] = trim($validated['full_name']);
        $validated['phone'] = $validated['phone'] ? trim($validated['phone']) : null;
        $validated['email'] = $validated['email'] ? strtolower(trim($validated['email'])) : null;
        $validated['is_active'] = true;

        Learner::create($validated);

        return redirect()->route('learners.index')->with('status', 'Đã thêm hồ sơ người học.');
    }

    public function edit(Learner $learner): View
    {
        $this->authorizeTrainingOffice();

        return view('work.learners.edit', [
            'learner' => $learner->load('department'),
            'departments' => Department::where('is_active', true)
                ->orWhere('id', $learner->department_id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Learner $learner): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        $request->merge(['student_code' => strtoupper(trim((string) $request->input('student_code')))]);
        $validated = $request->validate($this->rules($learner));

        $validated['student_code'] = strtoupper(trim($validated['student_code']));
        $validated['full_name'] = trim($validated['full_name']);
        $validated['phone'] = $validated['phone'] ? trim($validated['phone']) : null;
        $validated['email'] = $validated['email'] ? strtolower(trim($validated['email'])) : null;

        $learner->update($validated);

        return redirect()->route('learners.index')->with('status', 'Đã cập nhật hồ sơ người học.');
    }

    public function destroy(Learner $learner): RedirectResponse
    {
        $this->authorizeTrainingOffice();
        abort_if($learner->attendanceRecords()->exists(), 409, 'Không thể xóa hồ sơ đã có dữ liệu điểm danh; hãy cập nhật trạng thái học tập thay vì xóa.');
        $learner->delete();

        return redirect()->route('learners.index')->with('status', 'Đã xóa hồ sơ người học.');
    }

    private function rules(?Learner $learner = null, bool $checkUnique = true): array
    {
        return [
            'student_code' => array_filter(['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/', $checkUnique ? Rule::unique('learners', 'student_code')->ignore($learner?->id) : null]),
            'full_name' => ['required', 'string', 'max:160'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'cohort' => ['nullable', 'string', 'max:20'],
            'major' => ['nullable', 'string', 'max:160'],
            'class_name' => ['nullable', 'string', 'max:80'],
            'study_status' => ['required', Rule::in(['active', 'paused', 'inactive', 'graduated', 'dropped'])],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\s-]{7,30}$/'],
            'email' => ['nullable', 'email', 'max:120'],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(function ($query) use ($learner): void {
                    $query->where('is_active', true);
                    if ($learner?->department_id) {
                        $query->orWhere('id', $learner->department_id);
                    }
                }),
            ],
        ];
    }

    private function authorizeTrainingOffice(): void
    {
        abort_unless(auth()->user()->canManageLearners(), 403);
    }
}
