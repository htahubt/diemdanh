<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Learner;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:160'],
            'role' => ['nullable', Rule::in(array_keys($this->roles()))],
        ]);
        $users = User::query()
            ->with(['learner', 'lecturerProfile', 'department'])
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('work.accounts.index', [
            'users' => $users,
            'groupedUsers' => $users->getCollection()->groupBy('role'),
            'learners' => Learner::whereDoesntHave('user')->orderBy('student_code')->get(),
            'lecturers' => Lecturer::whereDoesntHave('user')->orderBy('lecturer_code')->get(),
            'departments' => Department::orderBy('name')->get(),
            'roles' => $this->roles(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate($this->rules());
        $validated['password'] = Hash::make($validated['password']);
        $validated['can_view_sensitive_profiles'] = $request->boolean('can_view_sensitive_profiles');
        $this->validateRoleLinks($validated);
        User::create($validated);

        return redirect()->route('accounts.index')->with('status', 'Đã tạo tài khoản.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate($this->rules($user));
        $this->validateRoleLinks($validated);

        if (filled($validated['password'] ?? null)) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        $validated['can_view_sensitive_profiles'] = $request->boolean('can_view_sensitive_profiles');
        $user->update($validated);

        return redirect()->route('accounts.index')->with('status', 'Đã cập nhật tài khoản.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_if($request->user()->is($user), 409, 'Không thể xóa tài khoản đang đăng nhập.');
        abort_if($user->role === 'admin' && User::where('role', 'admin')->count() <= 1, 409, 'Phải còn ít nhất một tài khoản quản trị.');
        $user->delete();

        return redirect()->route('accounts.index')->with('status', 'Đã xóa tài khoản.');
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:12', 'max:255'],
            'role' => ['required', Rule::in(array_keys($this->roles()))],
            'learner_id' => [
                Rule::requiredIf(fn (): bool => request('role') === 'learner'),
                'nullable', 'integer',
                Rule::exists('learners', 'id')->whereNotIn('id', User::whereNotNull('learner_id')->when($user, fn ($query) => $query->where('id', '!=', $user->id))->select('learner_id')),
            ],
            'lecturer_id' => [
                Rule::requiredIf(fn (): bool => request('role') === 'lecturer'),
                'nullable', 'integer',
                Rule::exists('lecturers', 'id')->whereNotIn('id', User::whereNotNull('lecturer_id')->when($user, fn ($query) => $query->where('id', '!=', $user->id))->select('lecturer_id')),
            ],
            'department_id' => [
                Rule::requiredIf(fn (): bool => in_array(request('role'), ['unit_leader', 'student_affairs'], true)),
                'nullable', 'integer', Rule::exists('departments', 'id'),
            ],
        ];
    }

    private function validateRoleLinks(array $data): void
    {
        if ($data['role'] !== 'learner' && ! empty($data['learner_id'])) {
            abort(422, 'Chỉ tài khoản người học mới được liên kết hồ sơ người học.');
        }
        if ($data['role'] !== 'lecturer' && ! empty($data['lecturer_id'])) {
            abort(422, 'Chỉ tài khoản giảng viên mới được liên kết hồ sơ giảng viên.');
        }
        if (! in_array($data['role'], ['unit_leader', 'student_affairs'], true) && ! empty($data['department_id'])) {
            abort(422, 'Chỉ tài khoản có phạm vi đơn vị mới được gán khoa/đơn vị.');
        }
    }

    private function roles(): array
    {
        return [
            'admin' => 'Quản trị hệ thống',
            'training_office' => 'Phòng đào tạo',
            'unit_leader' => 'Lãnh đạo khoa/viện',
            'student_affairs' => 'Cán bộ quản lý người học',
            'lecturer' => 'Giảng viên',
            'learner' => 'Học viên / sinh viên',
        ];
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }
}
