@extends('layouts.app')

@section('title', 'Tài khoản & vai trò')
@section('page_heading', 'Tài khoản & vai trò')

@section('content')
    <div class="page-heading account-page-heading">
        <div>
            <p class="eyebrow">HỆ THỐNG / QUẢN TRỊ DANH TÍNH</p>
            <h1>Tài khoản & vai trò</h1>
            <p class="page-lede">Quản lý quyền truy cập, đơn vị phụ trách và liên kết hồ sơ người học.</p>
        </div>
        <button class="button button-primary" type="button" data-dialog-open="createAccountDialog">+ Tạo tài khoản</button>
    </div>

    @if (session('status'))<div class="lecturer-flash" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>@endif

    <form class="account-filters" action="{{ route('accounts.index') }}" method="GET">
        <label class="lecturer-field account-search-field">
            <span>Tìm tài khoản</span>
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Họ tên hoặc email">
        </label>
        <label class="lecturer-field account-role-filter">
            <span>Nhóm vai trò</span>
            <select name="role">
                <option value="">Tất cả vai trò</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button class="button button-secondary" type="submit">Lọc danh sách</button>
        @if (($filters['search'] ?? '') !== '' || ($filters['role'] ?? '') !== '')
            <a class="account-clear-filter" href="{{ route('accounts.index') }}">Xóa lọc</a>
        @endif
        <span class="account-total">{{ $users->total() }} tài khoản</span>
    </form>

    <div class="account-groups">
        @if ($groupedUsers->isEmpty())
            <div class="account-empty-state">Không có tài khoản khớp với bộ lọc.</div>
        @else
            @foreach ($roles as $role => $label)
                @php $groupUsers = $groupedUsers->get($role, collect()); @endphp
                @if ($groupUsers->isNotEmpty())
                    <details class="account-group" open>
                        <summary>
                            <span class="account-group-name">{{ $label }}</span>
                            <span class="account-group-count">{{ $groupUsers->count() }}</span>
                            <span class="account-group-caret" aria-hidden="true"></span>
                        </summary>
                        <div class="account-user-list">
                            @foreach ($groupUsers as $user)
                                <article class="account-user-row">
                                    <div class="account-person">
                                        <span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                        <span class="account-person-copy"><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></span>
                                    </div>
                                    @php $linkedLecturer = $user->lecturerProfile ?? ($user->role === 'lecturer' ? $lecturers->firstWhere('email', $user->email) : null); @endphp
                                    <div class="account-scope">
                                        @if ($user->learner)
                                            <span>{{ $user->learner->student_code }} · {{ $user->learner->full_name }}</span>
                                        @elseif ($linkedLecturer)
                                            <span>{{ $linkedLecturer->lecturer_code }} · {{ $linkedLecturer->name }}</span>
                                        @elseif ($user->department)
                                            <span>{{ $user->department->name }}</span>
                                        @else
                                            <span class="account-scope-empty">Toàn hệ thống</span>
                                        @endif
                                        @if ($user->can_view_sensitive_profiles || $user->role === 'admin')
                                            <small>Quyền xem hồ sơ nhạy cảm</small>
                                        @endif
                                    </div>
                                    <div class="account-actions">
                                        <button
                                            class="button button-secondary account-edit-button"
                                            type="button"
                                            data-edit-account
                                            data-update-url="{{ route('accounts.update', $user) }}"
                                            data-name="{{ $user->name }}"
                                            data-email="{{ $user->email }}"
                                            data-role="{{ $user->role }}"
                                            data-learner-id="{{ $user->learner_id }}"
                                            data-learner-code="{{ $user->learner?->student_code }}"
                                            data-learner-name="{{ $user->learner?->full_name }}"
                                            data-lecturer-id="{{ $user->lecturer_id ?? $linkedLecturer?->id }}"
                                            data-lecturer-code="{{ $linkedLecturer?->lecturer_code }}"
                                            data-lecturer-name="{{ $linkedLecturer?->name }}"
                                            data-department-id="{{ $user->department_id }}"
                                            data-sensitive="{{ $user->can_view_sensitive_profiles ? '1' : '0' }}"
                                        >Sửa</button>
                                        @if (! auth()->user()->is($user))
                                            <form action="{{ route('accounts.destroy', $user) }}" method="POST" onsubmit="return confirm(this.dataset.confirm)" data-confirm="Xóa tài khoản {{ $user->email }}?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="button button-danger account-delete-button" type="submit">Xóa</button>
                                            </form>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </details>
                @endif
            @endforeach
        @endif
    </div>
    <div class="course-pagination">{{ $users->links() }}</div>

    <dialog class="account-dialog" id="createAccountDialog" aria-labelledby="createAccountTitle">
        <div class="account-dialog-heading">
            <div><p class="eyebrow">QUẢN TRỊ DANH TÍNH</p><h2 id="createAccountTitle">Tạo tài khoản</h2></div>
            <button class="account-dialog-close" type="button" data-dialog-close aria-label="Đóng">×</button>
        </div>
        <form class="account-dialog-form" action="{{ route('accounts.store') }}" method="POST" data-account-form>
            @csrf
            <label class="lecturer-field"><span>Họ tên</span><input name="name" required maxlength="160"></label>
            <label class="lecturer-field"><span>Email đăng nhập</span><input type="email" name="email" required maxlength="255" autocomplete="username"></label>
            <label class="lecturer-field"><span>Mật khẩu tạm</span><input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
            <label class="lecturer-field"><span>Vai trò</span><select name="role" required data-account-role><option value="" selected disabled>Chọn vai trò</option>@foreach ($roles as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
            <label class="lecturer-field"><span>Hồ sơ người học</span><select name="learner_id" data-account-learner><option value="">Không liên kết</option>@foreach ($learners as $learner)<option value="{{ $learner->id }}">{{ $learner->student_code }} · {{ $learner->full_name }}</option>@endforeach</select></label>
            <label class="lecturer-field"><span>Hồ sơ giảng viên</span><select name="lecturer_id" data-account-lecturer><option value="">Chọn hồ sơ giảng viên</option>@foreach ($lecturers as $lecturer)<option value="{{ $lecturer->id }}">{{ $lecturer->lecturer_code }} · {{ $lecturer->name }}</option>@endforeach</select></label>
            <label class="lecturer-field"><span>Đơn vị phụ trách</span><select name="department_id" data-account-department><option value="">Không gán đơn vị</option>@foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></label>
            <label class="account-sensitive-toggle"><input type="checkbox" name="can_view_sensitive_profiles" value="1"> Được xem hồ sơ nhạy cảm</label>
            <div class="account-dialog-actions"><button class="button button-secondary" type="button" data-dialog-close>Hủy</button><button class="button button-primary" type="submit">Tạo tài khoản</button></div>
        </form>
    </dialog>

    <dialog class="account-dialog" id="editAccountDialog" aria-labelledby="editAccountTitle">
        <div class="account-dialog-heading">
            <div><p class="eyebrow">QUẢN TRỊ DANH TÍNH</p><h2 id="editAccountTitle">Sửa tài khoản</h2></div>
            <button class="account-dialog-close" type="button" data-dialog-close aria-label="Đóng">×</button>
        </div>
        <form class="account-dialog-form" method="POST" data-account-form id="editAccountForm">
            @csrf
            @method('PUT')
            <label class="lecturer-field"><span>Họ tên</span><input name="name" required maxlength="160" data-account-name></label>
            <label class="lecturer-field"><span>Email đăng nhập</span><input type="email" name="email" required maxlength="255" autocomplete="username" data-account-email></label>
            <label class="lecturer-field"><span>Vai trò</span><select name="role" required data-account-role>@foreach ($roles as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
            <label class="lecturer-field"><span>Hồ sơ người học</span><select name="learner_id" data-account-learner><option value="">Không liên kết</option>@foreach ($learners as $learner)<option value="{{ $learner->id }}">{{ $learner->student_code }} · {{ $learner->full_name }}</option>@endforeach</select></label>
            <label class="lecturer-field"><span>Hồ sơ giảng viên</span><select name="lecturer_id" data-account-lecturer><option value="">Chọn hồ sơ giảng viên</option>@foreach ($lecturers as $lecturer)<option value="{{ $lecturer->id }}">{{ $lecturer->lecturer_code }} · {{ $lecturer->name }}</option>@endforeach</select></label>
            <label class="lecturer-field"><span>Đơn vị phụ trách</span><select name="department_id" data-account-department><option value="">Không gán đơn vị</option>@foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></label>
            <label class="lecturer-field"><span>Mật khẩu mới</span><input type="password" name="password" minlength="12" autocomplete="new-password" placeholder="Để trống để giữ nguyên"></label>
            <label class="account-sensitive-toggle"><input type="checkbox" name="can_view_sensitive_profiles" value="1" data-account-sensitive> Được xem hồ sơ nhạy cảm</label>
            <div class="account-dialog-actions"><button class="button button-secondary" type="button" data-dialog-close>Hủy</button><button class="button button-primary" type="submit">Lưu thay đổi</button></div>
        </form>
    </dialog>

    <script>
        const createAccountDialog = document.getElementById('createAccountDialog');
        const editAccountDialog = document.getElementById('editAccountDialog');

        document.querySelectorAll('[data-dialog-open]').forEach((button) => {
            button.addEventListener('click', () => document.getElementById(button.dataset.dialogOpen).showModal());
        });

        document.querySelectorAll('[data-dialog-close]').forEach((button) => {
            button.addEventListener('click', () => button.closest('dialog').close());
        });

        document.querySelectorAll('[data-account-form]').forEach((form) => {
            const role = form.querySelector('[data-account-role]');
            const learner = form.querySelector('[data-account-learner]');
            const lecturer = form.querySelector('[data-account-lecturer]');
            const department = form.querySelector('[data-account-department]');
            const syncRoleRequirements = () => {
                learner.required = role.value === 'learner';
                lecturer.required = role.value === 'lecturer';
                department.required = ['unit_leader', 'student_affairs'].includes(role.value);
                if (!learner.required) learner.value = '';
                if (!lecturer.required) lecturer.value = '';
                if (!department.required) department.value = '';
            };
            role.addEventListener('change', syncRoleRequirements);
            syncRoleRequirements();
        });

        document.querySelectorAll('[data-edit-account]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = document.getElementById('editAccountForm');
                form.action = button.dataset.updateUrl;
                form.querySelector('[data-account-name]').value = button.dataset.name;
                form.querySelector('[data-account-email]').value = button.dataset.email;
                form.querySelector('[data-account-role]').value = button.dataset.role;
                form.querySelector('[data-account-sensitive]').checked = button.dataset.sensitive === '1';

                const learnerSelect = form.querySelector('[data-account-learner]');
                learnerSelect.querySelectorAll('[data-current-learner]').forEach((option) => option.remove());
                const learnerId = button.dataset.learnerId;
                if (learnerId && !Array.from(learnerSelect.options).some((option) => option.value === learnerId)) {
                    const option = new Option(`${button.dataset.learnerCode} · ${button.dataset.learnerName}`, learnerId);
                    option.dataset.currentLearner = 'true';
                    learnerSelect.add(option);
                }
                learnerSelect.value = learnerId;

                const lecturerSelect = form.querySelector('[data-account-lecturer]');
                lecturerSelect.querySelectorAll('[data-current-lecturer]').forEach((option) => option.remove());
                const lecturerId = button.dataset.lecturerId;
                if (lecturerId && !Array.from(lecturerSelect.options).some((option) => option.value === lecturerId)) {
                    const option = new Option(`${button.dataset.lecturerCode} · ${button.dataset.lecturerName}`, lecturerId);
                    option.dataset.currentLecturer = 'true';
                    lecturerSelect.add(option);
                }
                lecturerSelect.value = lecturerId;
                form.querySelector('[data-account-department]').value = button.dataset.departmentId;
                form.querySelector('[data-account-role]').dispatchEvent(new Event('change'));
                editAccountDialog.showModal();
            });
        });

        [createAccountDialog, editAccountDialog].forEach((dialog) => {
            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) dialog.close();
            });
        });
    </script>
@endsection
