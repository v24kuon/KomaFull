@extends('layouts.admin')

@section('page-title', '追加項目編集')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @include('admin.partials._errors')

                <form method="POST" action="{{ route('admin.additional-items.update', $additionalItem) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.additional-items._form')
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">更新</button>
                        <a href="{{ route('admin.additional-items.index') }}" class="btn btn-outline-secondary">キャンセル</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
