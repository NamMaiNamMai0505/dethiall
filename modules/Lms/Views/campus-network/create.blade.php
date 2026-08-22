@extends('layouts.admin')

@section('title', 'Thêm Wi‑Fi trường')
@section('page-title', 'Thêm Wi‑Fi trường')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Wi‑Fi trường', 'url' => route('campus-network.index')],
    ['title' => 'Thêm mới'],
]" />

<div class="bg-white rounded-lg shadow max-w-2xl">
    <div class="px-6 py-4 border-b border-slate-100 font-semibold text-slate-800">Thêm cấu hình AP / dải IP</div>
    <form method="POST" action="{{ route('campus-network.store') }}" class="p-6">
        @csrf
        @include('lms::campus-network._form', ['network' => null])
        <div class="mt-6 flex gap-2">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Lưu</button>
            <a href="{{ route('campus-network.index') }}" class="inline-flex items-center px-4 py-2 border border-slate-200 text-sm rounded-lg text-slate-700 hover:bg-slate-50">Huỷ</a>
        </div>
    </form>
</div>
@endsection
