@extends('layouts.app')

@section('title', $item['label'])
@section('page_heading', $item['label'])

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">KHÔNG GIAN NGHIỆP VỤ</p>
            <h1>{{ $item['label'] }}</h1>
            <p class="page-lede">{{ $item['description'] }}</p>
        </div>
        <a class="button button-secondary" href="{{ route('dashboard') }}">← Tổng quan</a>
    </div>

    <section class="panel module-empty">
        <span class="empty-mark">{{ str_pad((string) (collect(config('attendance.groups'))->pluck('items')->flatten(1)->search(fn ($menuItem) => $menuItem['key'] === $item['key']) + 1), 2, '0', STR_PAD_LEFT) }}</span>
        <p class="eyebrow">ĐẦU MỐI PHỤ TRÁCH</p>
        <h2>{{ $item['owner'] }}</h2>
        <p>Chức năng này đang được thiết lập. Mục menu và phạm vi phụ trách đã được đưa vào khung điều hành.</p>
        <a class="button button-primary" href="{{ route('dashboard') }}">Về bảng tổng quan</a>
    </section>
@endsection