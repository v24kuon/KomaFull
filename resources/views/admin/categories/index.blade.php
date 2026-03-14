@extends('layouts.admin')

@section('page-title', 'カテゴリ管理')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-body-secondary mb-0">登録済みカテゴリの一覧</p>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">新規作成</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div id="categories-table">
            @include('admin.categories._table')
        </div>
    </div>
</div>
@endsection
