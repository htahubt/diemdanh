<div class="lecturer-form-section">
    <h2>Thông tin cá nhân</h2>
    <div class="lecturer-form-grid">
        <label class="lecturer-field">
            <span>Mã giảng viên <b>*</b></span>
            <input name="lecturer_code" value="{{ old('lecturer_code', $lecturer->lecturer_code ?? '') }}" maxlength="30" required autocomplete="off">
            @error('lecturer_code')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Họ và tên <b>*</b></span>
            <input name="name" value="{{ old('name', $lecturer->name ?? '') }}" maxlength="160" required autocomplete="name">
            @error('name')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Ngày sinh <b>*</b></span>
            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', isset($lecturer) ? $lecturer->date_of_birth?->format('Y-m-d') : '') }}" required>
            @error('date_of_birth')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Giới tính</span>
            <select name="gender">
                <option value="">Không kê khai</option>
                <option value="female" @selected(old('gender', $lecturer->gender ?? '') === 'female')>Nữ</option>
                <option value="male" @selected(old('gender', $lecturer->gender ?? '') === 'male')>Nam</option>
                <option value="other" @selected(old('gender', $lecturer->gender ?? '') === 'other')>Khác</option>
            </select>
            @error('gender')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Số điện thoại <b>*</b></span>
            <input type="tel" name="phone" value="{{ old('phone', $lecturer->phone ?? '') }}" maxlength="20" required autocomplete="tel">
            @error('phone')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Email <b>*</b></span>
            <input type="email" name="email" value="{{ old('email', $lecturer->email ?? '') }}" maxlength="255" required autocomplete="email">
            @error('email')<small class="field-error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>

@if ($canManageAccount)
    <div class="lecturer-form-section lecturer-account-section">
        <h2>Tài khoản đăng nhập</h2>
        <p class="lecturer-account-hint">Email đăng nhập dùng cùng email hồ sơ. Mật khẩu được tạo hoặc đổi ngay cùng hồ sơ này.</p>
        <div class="lecturer-form-grid">
            <label class="lecturer-field">
                <span>{{ isset($lecturer) && $linkedAccount ? 'Tài khoản đang liên kết' : 'Mật khẩu đăng nhập' }}@if (! isset($lecturer) || ! $linkedAccount) <b>*</b>@endif</span>
                @if (isset($lecturer) && $linkedAccount)
                    <input value="{{ $linkedAccount->email }}" readonly>
                @else
                    <input type="password" name="account_password" minlength="12" autocomplete="new-password" @required(! isset($lecturer) || ! $linkedAccount) placeholder="Ít nhất 12 ký tự">
                    @error('account_password')<small class="field-error">{{ $message }}</small>@enderror
                @endif
            </label>
            @if (! isset($lecturer) || ! $linkedAccount)
                <label class="lecturer-field">
                    <span>Xác nhận mật khẩu đăng nhập <b>*</b></span>
                    <input type="password" name="account_password_confirmation" minlength="12" autocomplete="new-password" required>
                </label>
            @else
                <label class="lecturer-field">
                    <span>Mật khẩu mới</span>
                    <input type="password" name="account_password" minlength="12" autocomplete="new-password" placeholder="Để trống nếu giữ nguyên">
                    @error('account_password')<small class="field-error">{{ $message }}</small>@enderror
                </label>
                <label class="lecturer-field">
                    <span>Xác nhận mật khẩu mới</span>
                    <input type="password" name="account_password_confirmation" minlength="12" autocomplete="new-password">
                </label>
            @endif
        </div>
    </div>
@endif

<div class="lecturer-form-section">
    <h2>Thông tin chuyên môn</h2>
    <div class="lecturer-form-grid">
        <label class="lecturer-field">
            <span>Học hàm <b>*</b></span>
            <select name="academic_rank_id" required>
                <option value="">Chọn học hàm</option>
                @foreach ($academicRanks as $rank)
                    <option value="{{ $rank->id }}" @selected((string) old('academic_rank_id', $lecturer->academic_rank_id ?? '') === (string) $rank->id)>{{ $rank->name }}</option>
                @endforeach
            </select>
            @error('academic_rank_id')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Học vị <b>*</b></span>
            <select name="academic_degree_id" required>
                <option value="">Chọn học vị</option>
                @foreach ($academicDegrees as $degree)
                    <option value="{{ $degree->id }}" @selected((string) old('academic_degree_id', $lecturer->academic_degree_id ?? '') === (string) $degree->id)>{{ $degree->name }}</option>
                @endforeach
            </select>
            @error('academic_degree_id')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Chuyên ngành <b>*</b></span>
            <input name="specialization" value="{{ old('specialization', $lecturer->specialization ?? '') }}" maxlength="160" required>
            @error('specialization')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Đơn vị công tác <b>*</b></span>
            <select name="department_id" required>
                <option value="">Chọn khoa / đơn vị</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) old('department_id', $lecturer->department_id ?? '') === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            @error('department_id')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Chức vụ</span>
            <input name="position" value="{{ old('position', $lecturer->position ?? '') }}" maxlength="160">
            @error('position')<small class="field-error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>

<div class="lecturer-form-section">
    <h2>Thông tin định danh</h2>
    <div class="lecturer-form-grid">
        <label class="lecturer-field">
            <span>Số CCCD <b>*</b></span>
            @if ($canViewSensitive)
                <input name="national_id" value="{{ old('national_id', $lecturer->national_id ?? '') }}" inputmode="numeric" pattern="[0-9]{12}" maxlength="12" @required(! isset($lecturer)) autocomplete="off">
            @else
                <span class="sensitive-hidden">Thông tin đã được lưu; tài khoản này không có quyền xem CCCD.</span>
            @endif
            @error('national_id')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Ngày cấp <b>*</b></span>
            <input type="date" name="id_issued_at" value="{{ old('id_issued_at', isset($lecturer) ? $lecturer->id_issued_at?->format('Y-m-d') : '') }}" required>
            @error('id_issued_at')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Nơi cấp <b>*</b></span>
            <input name="id_issued_place" value="{{ old('id_issued_place', $lecturer->id_issued_place ?? '') }}" maxlength="160" required>
            @error('id_issued_place')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Địa chỉ thường trú <b>*</b></span>
            <input name="permanent_address" value="{{ old('permanent_address', $lecturer->permanent_address ?? '') }}" maxlength="1000" required autocomplete="street-address">
            @error('permanent_address')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Địa chỉ liên hệ</span>
            <input name="contact_address" value="{{ old('contact_address', $lecturer->contact_address ?? '') }}" maxlength="1000">
            @error('contact_address')<small class="field-error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>

<div class="lecturer-form-section">
    <h2>Thông tin thanh toán</h2>
    <div class="lecturer-form-grid">
        <label class="lecturer-field">
            <span>Số tài khoản <b>*</b></span>
            @if ($canViewSensitive)
                <input name="bank_account_number" value="{{ old('bank_account_number', $lecturer->bank_account_number ?? '') }}" inputmode="numeric" pattern="[0-9]{6,20}" maxlength="20" @required(! isset($lecturer)) autocomplete="off">
            @else
                <span class="sensitive-hidden">Thông tin đã được lưu; tài khoản này không có quyền xem số tài khoản.</span>
            @endif
            @error('bank_account_number')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Tên ngân hàng <b>*</b></span>
            <input name="bank_name" value="{{ old('bank_name', $lecturer->bank_name ?? '') }}" maxlength="160" required>
            @error('bank_name')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Chi nhánh</span>
            <input name="bank_branch" value="{{ old('bank_branch', $lecturer->bank_branch ?? '') }}" maxlength="160">
            @error('bank_branch')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Tên chủ tài khoản <b>*</b></span>
            <input name="bank_account_name" value="{{ old('bank_account_name', $lecturer->bank_account_name ?? '') }}" maxlength="160" required>
            @error('bank_account_name')<small class="field-error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>

<div class="lecturer-form-section">
    <h2>Hồ sơ kèm theo</h2>
    <div class="lecturer-form-grid">
        <label class="lecturer-field">
            <span>CCCD (PDF, JPG, PNG; tối đa 10 MB/tệp)</span>
            <input type="file" name="identity_documents[]" accept=".pdf,.jpg,.jpeg,.png" multiple>
            @error('identity_documents.*')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Bằng cấp, chứng chỉ (PDF, JPG, PNG)</span>
            <input type="file" name="education_documents[]" accept=".pdf,.jpg,.jpeg,.png" multiple>
            @error('education_documents.*')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label class="lecturer-field">
            <span>Tài liệu khác (PDF, JPG, PNG)</span>
            <input type="file" name="other_documents[]" accept=".pdf,.jpg,.jpeg,.png" multiple>
            @error('other_documents.*')<small class="field-error">{{ $message }}</small>@enderror
        </label>
    </div>
    @if (isset($lecturer) && $lecturer->documents->isNotEmpty())
        <ul class="lecturer-document-list">
            @foreach ($lecturer->documents as $document)
                <li><a href="{{ route('lecturers.documents.download', [$lecturer, $document]) }}">{{ $document->original_name }}</a> <small>· {{ $document->created_at->format('d/m/Y') }}</small></li>
            @endforeach
        </ul>
    @endif
</div>

<div class="lecturer-form-section lecturer-final-section">
    <h2>Trạng thái hồ sơ</h2>
    <div class="lecturer-form-grid">
        <label class="lecturer-field">
            <span>Tình trạng công tác</span>
            <select name="status" required>
                <option value="active" @selected(old('status', $lecturer->status ?? 'active') === 'active')>Đang công tác</option>
                <option value="inactive" @selected(old('status', $lecturer->status ?? 'active') === 'inactive')>Ngừng công tác</option>
            </select>
        </label>
        <label class="lecturer-declaration">
            <input type="checkbox" name="declaration" value="1" @checked(old('declaration')) required>
            <span>Tôi đã kiểm tra và xác nhận các thông tin kê khai là chính xác. <b>*</b></span>
        </label>
    </div>
</div>

<div class="lecturer-form-actions">
    <a class="button button-secondary" href="{{ route('lecturers.index') }}">Hủy</a>
    <button class="button button-primary" type="submit">{{ $submitLabel }}</button>
</div>