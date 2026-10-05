@php
    $statusLabels = ['open' => 'Mới', 'in_progress' => 'Đang xử lý', 'resolved' => 'Đã xử lý'];
    $severityLabels = ['critical' => 'Cần xử lý sớm', 'warning' => 'Theo dõi'];
@endphp

@extends('layouts.app')

@section('title', 'Cảnh báo chuyên cần')
@section('page_heading', 'Cảnh báo chuyên cần')

@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">ĐIỀU HÀNH / THEO DÕI NGƯỜI HỌC</p><h1>Cảnh báo chuyên cần</h1><p class="page-lede">Điều kiện cảnh báo lấy từ ngưỡng cấu hình; mỗi lần xử lý được lưu trong nhật ký.</p></div>
        @if ($canRefreshAlerts)<form action="{{ route('attendance-alerts.refresh') }}" method="POST">@csrf<button class="button button-primary" type="submit">Quét cảnh báo</button></form>@endif
    </div>

    @if (session('status'))<div class="lecturer-flash" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>@endif

    @if ($settings->isNotEmpty())
    <section class="panel config-departments-panel">
        <div class="panel-heading"><div><p class="eyebrow">NGƯỠNG CẤU HÌNH</p><h2>Điều kiện phát hiện</h2></div><span class="panel-count">{{ $openCount }} ĐANG MỞ / XỬ LÝ</span></div>
        <div class="alert-settings-list">
            @foreach ($settings as $setting)
                <form class="alert-setting-row" action="{{ route('attendance-alert-settings.update', $setting) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div><strong>{{ $setting->label }}</strong><small>{{ $setting->rule_key }}</small></div>
                    <label class="sr-only" for="threshold-{{ $setting->id }}">{{ $setting->label }}</label>
                    <input id="threshold-{{ $setting->id }}" type="number" name="threshold" value="{{ $setting->threshold }}" min="0" max="10000" step="0.01" required>
                    <span>{{ $setting->unit }}</span>
                    <label class="alert-setting-toggle"><input type="checkbox" name="is_enabled" value="1" @checked($setting->is_enabled)> Bật</label>
                    <button class="button button-secondary alert-save" type="submit">Lưu</button>
                </form>
            @endforeach
        </div>
    </section>
    @endif

    <form class="course-filters" action="{{ route('attendance-alerts.index') }}" method="GET">
        <label class="lecturer-status-filter"><span class="sr-only">Trạng thái</span><select name="status"><option value="">Tất cả trạng thái</option>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="lecturer-status-filter"><span class="sr-only">Mức độ</span><select name="severity"><option value="">Tất cả mức độ</option>@foreach ($severityLabels as $value => $label)<option value="{{ $value }}" @selected(request('severity') === $value)>{{ $label }}</option>@endforeach</select></label>
        <button class="button button-secondary" type="submit">Lọc</button>
        @if (request()->hasAny(['status', 'severity']))<a class="config-toggle" href="{{ route('attendance-alerts.index') }}">Xóa lọc</a>@endif
    </form>

    <section class="panel config-departments-panel">
        <div class="config-table-wrap">
            <table class="config-table alert-table">
                <thead><tr><th>Cảnh báo</th><th>Đối tượng</th><th>Phát hiện gần nhất</th><th>Mức độ / trạng thái</th><th>Phân công / xử lý</th><th>Lịch sử</th></tr></thead>
                <tbody>
                    @forelse ($alerts as $alert)
                        <tr>
                            <td><strong>{{ $alert->title }}</strong><small class="course-description">{{ $alert->description }}</small></td>
                            <td>{{ $alert->learner_code ?: $alert->section_code ?: 'Toàn hệ thống' }}</td>
                            <td>{{ $alert->last_detected_at->format('d/m/Y H:i') }}</td>
                            <td><span class="lecturer-status {{ $alert->severity === 'critical' ? 'profile-needs_revision' : 'profile-submitted' }}">{{ $severityLabels[$alert->severity] ?? $alert->severity }}</span><small class="course-description">{{ $statusLabels[$alert->status] ?? $alert->status }}</small></td>
                            <td>
                                @if ($alert->status !== 'resolved')
                                    <form class="alert-action-form" action="{{ route('attendance-alerts.update', $alert) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <select name="assigned_to" aria-label="Cán bộ phụ trách"><option value="">Chưa phân công</option>@foreach ($staff as $staffMember)<option value="{{ $staffMember->id }}" @selected($alert->assigned_to == $staffMember->id)>{{ $staffMember->name }}</option>@endforeach</select>
                                        <select name="status" aria-label="Trạng thái xử lý">@foreach (['open' => 'Mới', 'in_progress' => 'Đang xử lý', 'resolved' => 'Đã xử lý'] as $value => $label)<option value="{{ $value }}" @selected($alert->status === $value)>{{ $label }}</option>@endforeach</select>
                                        <input name="action_note" required minlength="5" maxlength="2000" placeholder="Kết quả liên hệ / xử lý">
                                        <button class="button button-secondary alert-save" type="submit">Ghi nhận</button>
                                    </form>
                                @else
                                    <span>{{ $alert->resolvedBy?->name ?? 'Đã xử lý' }}</span><small class="course-description">{{ $alert->resolution_note }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($alert->histories->isNotEmpty())
                                    <details class="attendance-history"><summary>{{ $alert->histories->count() }} lần</summary>@foreach ($alert->histories as $history)<small>{{ $history->actor?->name ?? 'Hệ thống' }} · {{ $history->before_status ?: '—' }} → {{ $history->after_status }} · {{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->action_note }}</small>@endforeach</details>
                                @else
                                    <span class="course-description">Chưa xử lý</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="lecturer-empty">Chưa có cảnh báo phù hợp. Chạy quét để cập nhật trạng thái.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="course-pagination">{{ $alerts->links() }}</div>
    </section>
@endsection
