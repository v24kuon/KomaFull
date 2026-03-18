@extends('layouts.admin')

@section('page-title', '繰り返し設定管理')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-body-secondary mb-0">プログラムごとの繰り返し設定を管理し、1件ずつセッション生成を実行できます。</p>
    <a href="{{ route('admin.program-repetition-rules.create') }}" class="btn btn-primary">新規作成</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div id="program-repetition-rules-table">
            @include('partials.admin.program-repetition-rules.table')
        </div>
    </div>
</div>
@endsection
