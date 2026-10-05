<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use App\Models\LecturerDocument;
use App\Models\AcademicOption;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LecturerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'profile_status' => ['nullable', Rule::in(['submitted', 'needs_revision', 'approved'])],
        ]);

        $visibleLecturers = Lecturer::query();

        if (! $request->user()->canViewSensitiveProfiles()) {
            if ($request->user()->role === 'lecturer') {
                $visibleLecturers->whereKey($request->user()->lecturerProfileId() ?? 0);
            } else {
                $visibleLecturers->where('email', $request->user()->email);
            }
        }

        $lecturers = (clone $visibleLecturers)
            ->with(['departmentUnit', 'academicRank', 'academicDegree'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('lecturer_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['profile_status'] ?? null, fn (Builder $query, string $status) => $query->where('profile_status', $status))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('work.lecturers.index', [
            'lecturers' => $lecturers,
            'canManageAll' => $request->user()->canViewSensitiveProfiles(),
            'canCreateProfile' => $request->user()->canViewSensitiveProfiles()
                || $request->user()->lecturerProfileId() === null,
            'totalCount' => (clone $visibleLecturers)->count(),
            'pendingCount' => (clone $visibleLecturers)->where('profile_status', 'submitted')->count(),
            'revisionCount' => (clone $visibleLecturers)->where('profile_status', 'needs_revision')->count(),
            'approvedCount' => (clone $visibleLecturers)->where('profile_status', 'approved')->count(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_if(
            ! $request->user()->canViewSensitiveProfiles()
                && $request->user()->lecturerProfileId() !== null,
            409,
            'Tài khoản đã có hồ sơ giảng viên.',
        );

        return view('work.lecturers.create', array_merge([
            'canViewSensitive' => true,
            'canManageAccount' => $request->user()->canViewSensitiveProfiles(),
            'linkedAccount' => null,
        ], $this->formOptions()));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProfile($request);
        $uploads = $this->extractUploads($validated);
        unset($validated['declaration']);
        $user = $request->user();
        $linkedAccount = $user->canViewSensitiveProfiles()
            ? User::where('email', $validated['email'])->first()
            : $user;

        if (! $user->canViewSensitiveProfiles()) {
            abort_if($user->lecturerProfileId() !== null, 409);
            abort_unless($validated['email'] === $user->email, 403);
        }

        if ($linkedAccount && ($linkedAccount->role !== 'lecturer' || $linkedAccount->lecturer_id !== null)) {
            throw ValidationException::withMessages(['email' => 'Email này đã thuộc một tài khoản khác hoặc đã liên kết hồ sơ giảng viên.']);
        }
        $accountPassword = $this->validateAccountPassword($request, $linkedAccount === null && $user->canViewSensitiveProfiles());

        $validated = $this->mapAcademicReferences($validated);
        $validated['national_id_hash'] = hash_hmac('sha256', $validated['national_id'], config('app.key'));
        $validated['status'] = 'active';
        $validated['profile_status'] = 'submitted';
        $validated['submitted_at'] = now();

        DB::transaction(function () use ($validated, $uploads, $request, $linkedAccount, $accountPassword): void {
            $lecturer = Lecturer::create($validated);
            if ($linkedAccount) {
                $accountData = [
                    'name' => $lecturer->name,
                    'email' => $lecturer->email,
                    'lecturer_id' => $lecturer->id,
                ];
                if (filled($accountPassword)) {
                    $accountData['password'] = Hash::make($accountPassword);
                }
                $linkedAccount->update($accountData);
            } else {
                User::create([
                    'name' => $lecturer->name,
                    'email' => $lecturer->email,
                    'password' => Hash::make($accountPassword),
                    'role' => 'lecturer',
                    'lecturer_id' => $lecturer->id,
                ]);
            }
            $uploaded = $this->saveUploads($lecturer, $uploads, $request);
            $changes = $validated;

            if ($uploaded) {
                $changes['documents_added'] = $uploaded;
            }

            $this->recordHistory($lecturer, $request, 'submitted', $changes, 'Giảng viên tự kê khai và xác nhận thông tin.');
        });

        return redirect()->route('lecturers.index')->with('status', 'Đã gửi hồ sơ và tạo/liên kết tài khoản đăng nhập giảng viên.');
    }

    public function show(Request $request, Lecturer $lecturer): View
    {
        abort_unless($request->user()->canViewLecturerProfile($lecturer), 403);
        $canViewSensitive = $request->user()->canViewLecturerProfile($lecturer);
        $lecturer->load(['departmentUnit', 'academicRank', 'academicDegree', 'history']);

        if ($canViewSensitive) {
            $lecturer->load('documents');
        }

        return view('work.lecturers.show', compact('lecturer', 'canViewSensitive'));
    }

    public function edit(Request $request, Lecturer $lecturer): View
    {
        abort_unless($request->user()->canViewLecturerProfile($lecturer), 403);
        $canViewSensitive = $request->user()->canViewLecturerProfile($lecturer);
        $lecturer->load(['history']);

        if ($canViewSensitive) {
            $lecturer->load('documents');
        }

        return view('work.lecturers.edit', array_merge(
            [
                'lecturer' => $lecturer,
                'canViewSensitive' => $canViewSensitive,
                'canManageAccount' => $request->user()->canViewSensitiveProfiles(),
                'linkedAccount' => $this->accountForLecturer($lecturer),
            ],
            $this->formOptions($lecturer),
        ));
    }

    public function update(Request $request, Lecturer $lecturer): RedirectResponse
    {
        abort_unless($request->user()->canViewLecturerProfile($lecturer), 403);
        $validated = $this->validateProfile($request, $lecturer);
        $uploads = $this->extractUploads($validated);
        unset($validated['declaration']);
        $linkedAccount = $this->accountForLecturer($lecturer);

        if (! $request->user()->canViewSensitiveProfiles()) {
            abort_unless(
                $request->user()->email === $lecturer->email
                    || $request->user()->isAssignedToLecturer($lecturer->id),
                403,
            );
        }

        $emailOwner = User::where('email', $validated['email'])->first();
        if ($emailOwner && (! $linkedAccount || ! $emailOwner->is($linkedAccount))) {
            throw ValidationException::withMessages(['email' => 'Email này đang được sử dụng bởi tài khoản khác.']);
        }
        $accountPassword = $this->validateAccountPassword(
            $request,
            $linkedAccount === null && $request->user()->canViewSensitiveProfiles(),
        );

        $validated = $this->mapAcademicReferences($validated);

        if (! empty($validated['national_id'])) {
            $validated['national_id_hash'] = hash_hmac('sha256', $validated['national_id'], config('app.key'));
        } else {
            unset($validated['national_id']);
        }

        if (empty($validated['bank_account_number'])) {
            unset($validated['bank_account_number']);
        }

        $changes = $this->diff($lecturer, $validated);
        $identityChanged = array_key_exists('national_id', $changes)
            || array_key_exists('id_issued_at', $changes)
            || array_key_exists('id_issued_place', $changes);
        $bankChanged = array_key_exists('bank_account_number', $changes)
            || array_key_exists('bank_name', $changes)
            || array_key_exists('bank_branch', $changes)
            || array_key_exists('bank_account_name', $changes);
        $validated['profile_status'] = 'submitted';
        $validated['submitted_at'] = now();
        $validated['approved_at'] = null;
        $validated['reviewed_by'] = null;
        $validated['review_note'] = null;

        if ($identityChanged) {
            $validated['identity_verified_at'] = null;
        }

        if ($bankChanged) {
            $validated['bank_verified_at'] = null;
        }

        DB::transaction(function () use ($lecturer, $validated, $uploads, $request, $changes, $linkedAccount, $accountPassword): void {
            $lecturer->update($validated);
            $accountData = [
                'name' => $lecturer->name,
                'email' => $lecturer->email,
                'lecturer_id' => $lecturer->id,
            ];
            if (filled($accountPassword)) {
                $accountData['password'] = Hash::make($accountPassword);
            }
            if ($linkedAccount) {
                $linkedAccount->update($accountData);
            } else {
                $accountData['password'] = Hash::make($accountPassword);
                $accountData['role'] = 'lecturer';
                User::create($accountData);
            }
            $uploaded = $this->saveUploads($lecturer, $uploads, $request);

            if ($uploaded) {
                $changes['documents_added'] = $uploaded;
            }

            $this->recordHistory($lecturer, $request, 'profile_updated', $changes, 'Hồ sơ được cập nhật và gửi lại để kiểm tra.');
        });

        return redirect()->route('lecturers.index')->with('status', 'Đã cập nhật hồ sơ và thông tin tài khoản đăng nhập.');
    }

    public function destroy(Lecturer $lecturer): RedirectResponse
    {
        abort_unless(auth()->user()->canViewSensitiveProfiles(), 403);
        abort_if($lecturer->courseSections()->exists(), 409, 'Không thể xóa giảng viên đang được phân công lớp học phần.');
        abort_if($lecturer->profile_status === 'approved', 409, 'Hồ sơ đã xác nhận không thể xóa.');

        $documents = $lecturer->documents;
        DB::transaction(function () use ($lecturer): void {
            $this->accountForLecturer($lecturer)?->delete();
            $lecturer->delete();
        });

        foreach ($documents as $document) {
            Storage::disk('local')->delete($document->storage_path);
        }

        return redirect()->route('lecturers.index')->with('status', 'Đã xóa hồ sơ giảng viên.');
    }

    public function review(Request $request, Lecturer $lecturer): RedirectResponse
    {
        abort_unless($request->user()->canViewSensitiveProfiles(), 403);
        abort_unless($lecturer->profile_status === 'submitted', 409);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'needs_revision'])],
            'review_note' => ['required_if:action,needs_revision', 'nullable', 'string', 'max:2000'],
            'identity_verified' => ['exclude_unless:action,approve', 'required', 'accepted'],
            'bank_verified' => ['exclude_unless:action,approve', 'required', 'accepted'],
        ]);

        $approved = $validated['action'] === 'approve';
        $previousStatus = $lecturer->profile_status;
        DB::transaction(function () use ($lecturer, $request, $validated, $approved, $previousStatus): void {
            $lecturer->update([
                'profile_status' => $approved ? 'approved' : 'needs_revision',
                'review_note' => $validated['review_note'] ?? null,
                'reviewed_by' => $request->user()?->id,
                'approved_at' => $approved ? now() : null,
                'identity_verified_at' => $approved ? now() : null,
                'bank_verified_at' => $approved ? now() : null,
            ]);

            $this->recordHistory(
                $lecturer,
                $request,
                $approved ? 'approved' : 'revision_requested',
                ['profile_status' => ['from' => $previousStatus, 'to' => $lecturer->profile_status]],
                $validated['review_note'] ?? null,
            );
        });

        return redirect()->route('lecturers.edit', $lecturer)->with('status', $approved
            ? 'Đã xác nhận hồ sơ, CCCD và thông tin thanh toán.'
            : 'Đã yêu cầu giảng viên bổ sung, chỉnh sửa hồ sơ.');
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless(auth()->user()->canViewSensitiveProfiles(), 403);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Mã giảng viên', 'Họ tên', 'Ngày sinh', 'Học hàm', 'Học vị', 'Chuyên ngành',
                'Đơn vị', 'Chức vụ', 'Email', 'Điện thoại', 'Tình trạng công tác',
            ]);

            Lecturer::where('profile_status', 'approved')->orderBy('name')->chunk(200, function ($lecturers) use ($output): void {
                foreach ($lecturers as $lecturer) {
                    fputcsv($output, [
                        $lecturer->lecturer_code,
                        $lecturer->name,
                        $lecturer->date_of_birth?->format('d/m/Y'),
                        $lecturer->academic_rank,
                        $lecturer->academic_title,
                        $lecturer->specialization,
                        $lecturer->department,
                        $lecturer->position,
                        $lecturer->email,
                        $lecturer->phone,
                        $lecturer->status === 'active' ? 'Đang công tác' : 'Ngừng công tác',
                    ]);
                }
            });

            fclose($output);
        }, 'danh-sach-giang-vien-da-xac-nhan.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function downloadDocument(Request $request, Lecturer $lecturer, LecturerDocument $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($request->user()->canViewLecturerProfile($lecturer), 403);
        abort_unless($lecturer->documents()->whereKey($document->id)->exists(), 404);
        abort_unless(Storage::disk('local')->exists($document->storage_path), 404);

        return Storage::disk('local')->download($document->storage_path, basename($document->original_name));
    }

    private function rules(?Lecturer $lecturer = null): array
    {
        $codeRule = Rule::unique('lecturers', 'lecturer_code');
        $emailRule = Rule::unique('lecturers', 'email');
        $nationalIdRules = [$lecturer ? 'nullable' : 'required', 'regex:/^\d{12}$/'];
        $bankAccountRules = [$lecturer ? 'nullable' : 'required', 'regex:/^\d{6,20}$/'];
        $departmentRule = Rule::exists('departments', 'id')->where(function ($query) use ($lecturer): void {
            $query->where('is_active', true);

            if ($lecturer?->department_id) {
                $query->orWhere('id', $lecturer->department_id);
            }
        });
        $rankRule = Rule::exists('academic_options', 'id')->where(function ($query) use ($lecturer): void {
            $query->where('type', 'rank')->where(function ($query) use ($lecturer): void {
                $query->where('is_active', true);

                if ($lecturer?->academic_rank_id) {
                    $query->orWhere('id', $lecturer->academic_rank_id);
                }
            });
        });
        $degreeRule = Rule::exists('academic_options', 'id')->where(function ($query) use ($lecturer): void {
            $query->where('type', 'degree')->where(function ($query) use ($lecturer): void {
                $query->where('is_active', true);

                if ($lecturer?->academic_degree_id) {
                    $query->orWhere('id', $lecturer->academic_degree_id);
                }
            });
        });

        if ($lecturer) {
            $codeRule->ignore($lecturer->id);
            $emailRule->ignore($lecturer->id);
        }

        return [
            'lecturer_code' => ['required', 'string', 'max:30', $codeRule],
            'name' => ['required', 'string', 'max:160'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'other'])],
            'academic_rank_id' => ['required', 'integer', $rankRule],
            'academic_degree_id' => ['required', 'integer', $degreeRule],
            'specialization' => ['required', 'string', 'max:160'],
            'department_id' => ['required', 'integer', $departmentRule],
            'position' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'phone' => ['required', 'string', 'regex:/^(?:0|\+?84)(?:3|5|7|8|9)\d{8}$/'],
            'national_id' => $nationalIdRules,
            'id_issued_at' => ['required', 'date', 'before_or_equal:today'],
            'id_issued_place' => ['required', 'string', 'max:160'],
            'permanent_address' => ['required', 'string', 'max:1000'],
            'contact_address' => ['nullable', 'string', 'max:1000'],
            'bank_account_number' => $bankAccountRules,
            'bank_name' => ['required', 'string', 'max:160'],
            'bank_branch' => ['nullable', 'string', 'max:160'],
            'bank_account_name' => ['required', 'string', 'max:160'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'declaration' => ['accepted'],
            'identity_documents' => ['nullable', 'array', 'max:5'],
            'identity_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'education_documents' => ['nullable', 'array', 'max:5'],
            'education_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'other_documents' => ['nullable', 'array', 'max:5'],
            'other_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    private function validateProfile(Request $request, ?Lecturer $lecturer = null): array
    {
        $phone = $request->input('phone');

        if (is_string($phone)) {
            $request->merge(['phone' => preg_replace('/[\s().-]+/', '', $phone)]);
        }

        $validator = Validator::make($request->all(), $this->rules($lecturer));
        $validator->after(function ($validator) use ($request, $lecturer): void {
            $nationalId = $request->input('national_id');

            if (! is_string($nationalId) || ! preg_match('/^\d{12}$/', $nationalId)) {
                return;
            }

            $fingerprint = hash_hmac('sha256', $nationalId, config('app.key'));
            $existing = Lecturer::where('national_id_hash', $fingerprint)
                ->when($lecturer, fn (Builder $query) => $query->whereKeyNot($lecturer->id))
                ->exists();

            if ($existing) {
                $validator->errors()->add('national_id', 'Số CCCD này đã được sử dụng.');
            }
        });

        return $validator->validate();
    }

    private function extractUploads(array &$validated): array
    {
        $uploads = [];

        foreach (['identity_documents', 'education_documents', 'other_documents'] as $type) {
            $uploads[$type] = $validated[$type] ?? [];
            unset($validated[$type]);
        }

        return $uploads;
    }

    private function saveUploads(Lecturer $lecturer, array $uploads, Request $request): array
    {
        $saved = [];

        foreach ($uploads as $type => $files) {
            foreach ($files as $file) {
                $path = $file->store('lecturer-documents/' . $lecturer->id, 'local');

                if (! $path) {
                    throw new \RuntimeException('Không thể lưu tệp hồ sơ.');
                }

                $lecturer->documents()->create([
                    'document_type' => $type,
                    'original_name' => $file->getClientOriginalName(),
                    'storage_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => $request->user()?->id,
                ]);
                $saved[] = ['type' => $type, 'name' => $file->getClientOriginalName()];
            }
        }

        return $saved;
    }

    private function recordHistory(Lecturer $lecturer, Request $request, string $action, array $changes, ?string $note = null): void
    {
        $lecturer->history()->create([
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'changes' => $changes,
            'note' => $note,
        ]);
    }

    private function diff(Lecturer $lecturer, array $values): array
    {
        $changes = [];

        foreach ($values as $field => $newValue) {
            $oldValue = $lecturer->getAttribute($field);
            $oldValue = $oldValue instanceof \DateTimeInterface ? $oldValue->format('Y-m-d') : $oldValue;

            if ($oldValue !== $newValue) {
                $changes[$field] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        return $changes;
    }

    private function formOptions(?Lecturer $lecturer = null): array
    {
        $departments = Department::query()->where(function (Builder $query) use ($lecturer): void {
            $query->where('is_active', true);

            if ($lecturer?->department_id) {
                $query->orWhere('id', $lecturer->department_id);
            }
        })->orderBy('name')->get();

        $academicRanks = AcademicOption::query()->where('type', 'rank')->where(function (Builder $query) use ($lecturer): void {
            $query->where('is_active', true);

            if ($lecturer?->academic_rank_id) {
                    $query->orWhere('id', $lecturer->academic_rank_id);
            }
        })->orderBy('name')->get();

        $academicDegrees = AcademicOption::query()->where('type', 'degree')->where(function (Builder $query) use ($lecturer): void {
            $query->where('is_active', true);

            if ($lecturer?->academic_degree_id) {
                    $query->orWhere('id', $lecturer->academic_degree_id);
            }
        })->orderBy('name')->get();

        return compact('departments', 'academicRanks', 'academicDegrees');
    }

    private function accountForLecturer(Lecturer $lecturer): ?User
    {
        return User::query()
            ->where(fn (Builder $query) => $query
                ->where('lecturer_id', $lecturer->id)
                ->orWhere(fn (Builder $query) => $query->where('role', 'lecturer')->where('email', $lecturer->email)))
            ->first();
    }

    private function validateAccountPassword(Request $request, bool $required): ?string
    {
        $validated = $request->validate([
            'account_password' => ['nullable', 'string', 'min:12', 'max:255', 'confirmed'],
        ]);
        $password = $validated['account_password'] ?? null;

        if ($required && blank($password)) {
            throw ValidationException::withMessages(['account_password' => 'Cần đặt mật khẩu đăng nhập cho tài khoản giảng viên mới.']);
        }

        return $password;
    }

    private function mapAcademicReferences(array $validated): array
    {
        $department = Department::findOrFail($validated['department_id']);
        $rank = AcademicOption::where('type', 'rank')->findOrFail($validated['academic_rank_id']);
        $degree = AcademicOption::where('type', 'degree')->findOrFail($validated['academic_degree_id']);

        return array_merge($validated, [
            'department' => $department->name,
            'academic_rank' => $rank->name,
            'academic_title' => $degree->name,
        ]);
    }
}
