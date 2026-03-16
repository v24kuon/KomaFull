@extends('layouts.admin')

@section('page-title', '繰り返し設定作成')

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.program-repetition-rules.index') }}" class="btn btn-link px-0">← 一覧へ戻る</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.program-repetition-rules.store') }}">
            @csrf
            @include('admin.program-repetition-rules._form')
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.program-repetition-rules.index') }}" class="btn btn-outline-secondary">キャンセル</a>
                <button type="submit" class="btn btn-primary">作成する</button>
            </div>
        </form>
    </div>
</div>
@endsection
