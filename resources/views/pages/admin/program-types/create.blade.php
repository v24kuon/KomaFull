@extends('layouts.admin')

@section('page-title', 'プログラム種別作成')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @include('partials.admin.errors')

                <form method="POST" action="{{ route('admin.program-types.store') }}">
                    @csrf
                    @include('partials.admin.program-types.form')
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">作成</button>
                        <a href="{{ route('admin.program-types.index') }}" class="btn btn-outline-secondary">キャンセル</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
