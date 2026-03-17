@extends('layouts.admin')

@section('page-title', 'スタッフ管理')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-body-secondary mb-0">登録済みスタッフの一覧</p>
    <a href="{{ route('admin.staffs.create') }}" class="btn btn-primary">新規作成</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div id="staffs-table">
            @include('partials.admin.staffs.table')
        </div>
    </div>
</div>
@endsection
