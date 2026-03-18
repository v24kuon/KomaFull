@extends('layouts.admin')

@section('page-title', '繰り返し設定編集')

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.program-repetition-rules.index') }}" class="btn btn-link px-0">← 一覧へ戻る</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.program-repetition-rules.update', $programRepetitionRule) }}">
            @csrf
            @method('PUT')
            @include('partials.admin.program-repetition-rules.form')
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.program-repetition-rules.index') }}" class="btn btn-outline-secondary">キャンセル</a>
                <button type="submit" class="btn btn-primary">更新する</button>
            </div>
        </form>
    </div>
</div>
@endsection
