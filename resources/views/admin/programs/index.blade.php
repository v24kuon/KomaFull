@extends('layouts.admin')

@section('page-title', 'プログラム管理')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-body-secondary mb-0">登録済みプログラムの一覧</p>
    <a href="{{ route('admin.programs.create') }}" class="btn btn-primary">新規作成</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div id="programs-table">
            @include('admin.programs._table')
        </div>
    </div>
</div>
@endsection
