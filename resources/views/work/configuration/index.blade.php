@extends('layouts.app')

@section('title', 'Cấu hình học hàm / học vị')
@section('page_heading', 'Cấu hình')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">QUẢN TRỊ / DỮ LIỆU THAM CHIẾU</p>
            <h1>Cấu hình học hàm / học vị</h1>
            <p class="page-lede">Quản lý các lựa chọn học hàm và học vị dùng chung trong hồ sơ giảng viên.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="lecturer-flash" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="lecturer-flash config-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="config-academic-grid">
        <section class="panel config-academic-panel">
            <div class="panel-heading">
                <div><p class="eyebrow">DỮ LIỆU THAM CHIẾU</p><h2>Học hàm</h2></div>
                <span class="panel-count">{{ $academicRanks->count() }} LỰA CHỌN</span>
            </div>
            <form class="config-add-form config-option-form" action="{{ route('configuration.academic-options.store') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="rank">
                <label class="lecturer-field"><span>Tên học hàm</span><input name="name" value="{{ old('type') === 'rank' ? old('name') : '' }}" maxlength="120" required placeholder="Ví dụ: Giáo sư"></label>
                <button class="button button-primary" type="submit">Thêm</button>
            </form>
            <ul class="config-option-list">
                @forelse ($academicRanks as $rank)
                    <li>
                        <span><strong>{{ $rank->name }}</strong><small>{{ $rank->rank_lecturers_count }} hồ sơ</small></span>
                        @if (! $rank->rank_lecturers_count)
                            <form action="{{ route('configuration.academic-options.toggle', $rank) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button class="config-toggle" type="submit">{{ $rank->is_active ? 'Ngừng dùng' : 'Kích hoạt' }}</button>
                            </form>
                        @else
                            <span class="config-locked">Đang dùng</span>
                        @endif
                    </li>
                @empty
                    <li><span>Chưa khai báo học hàm.</span></li>
                @endforelse
            </ul>
        </section>

        <section class="panel config-academic-panel">
            <div class="panel-heading">
                <div><p class="eyebrow">DỮ LIỆU THAM CHIẾU</p><h2>Học vị</h2></div>
                <span class="panel-count">{{ $academicDegrees->count() }} LỰA CHỌN</span>
            </div>
            <form class="config-add-form config-option-form" action="{{ route('configuration.academic-options.store') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="degree">
                <label class="lecturer-field"><span>Tên học vị</span><input name="name" value="{{ old('type') === 'degree' ? old('name') : '' }}" maxlength="120" required placeholder="Ví dụ: Thạc sĩ"></label>
                <button class="button button-primary" type="submit">Thêm</button>
            </form>
            <ul class="config-option-list">
                @forelse ($academicDegrees as $degree)
                    <li>
                        <span><strong>{{ $degree->name }}</strong><small>{{ $degree->degree_lecturers_count }} hồ sơ</small></span>
                        @if (! $degree->degree_lecturers_count)
                            <form action="{{ route('configuration.academic-options.toggle', $degree) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button class="config-toggle" type="submit">{{ $degree->is_active ? 'Ngừng dùng' : 'Kích hoạt' }}</button>
                            </form>
                        @else
                            <span class="config-locked">Đang dùng</span>
                        @endif
                    </li>
                @empty
                    <li><span>Chưa khai báo học vị.</span></li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection