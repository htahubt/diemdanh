@php
    $section = $section ?? null;
    $weekdays = [1 => 'Thứ Hai', 2 => 'Thứ Ba', 3 => 'Thứ Tư', 4 => 'Thứ Năm', 5 => 'Thứ Sáu', 6 => 'Thứ Bảy', 7 => 'Chủ nhật'];
@endphp

<div class="section-schedule-group">
    <p class="eyebrow">THỜI KHÓA BIỂU VÀ ĐƠN VỊ PHỤ TRÁCH</p>
    <div class="section-schedule-grid">
        <label class="lecturer-field"><span>Đơn vị quản lý</span>
            <select name="department_id" required>
                <option value="">Chọn đơn vị</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id', $section?->department_id ?? $section?->course?->department_id) == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="lecturer-field"><span>Hình thức đào tạo</span>
            <select name="training_format" required>
                @foreach (['Chính quy', 'Vừa làm vừa học', 'Từ xa', 'Khác'] as $format)
                    <option value="{{ $format }}" @selected(old('training_format', $section?->training_format ?? 'Chính quy') === $format)>{{ $format }}</option>
                @endforeach
            </select>
        </label>
        <label class="lecturer-field"><span>Thứ học trong tuần</span>
            <select name="weekday" required>
                @foreach ($weekdays as $day => $label)
                    <option value="{{ $day }}" @selected(old('weekday', $section?->weekday) == $day)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="lecturer-field"><span>Phòng học</span><input name="room" value="{{ old('room', $section?->room) }}" maxlength="100" required></label>
        <label class="lecturer-field"><span>Giờ bắt đầu</span><input type="time" name="start_time" value="{{ old('start_time', $section?->start_time) }}" required></label>
        <label class="lecturer-field"><span>Giờ kết thúc</span><input type="time" name="end_time" value="{{ old('end_time', $section?->end_time) }}" required></label>
        <label class="lecturer-field"><span>Số buổi dự kiến</span><input type="number" name="expected_sessions" value="{{ old('expected_sessions', $section?->expected_sessions ?? 15) }}" min="1" max="100" required></label>
        <label class="lecturer-field"><span>Ngày bắt đầu học</span><input type="date" name="starts_on" value="{{ old('starts_on', $section?->starts_on?->format('Y-m-d')) }}" required></label>
        <label class="lecturer-field"><span>Ngày kết thúc học</span><input type="date" name="ends_on" value="{{ old('ends_on', $section?->ends_on?->format('Y-m-d')) }}" required></label>
    </div>
</div>
