@extends('layouts.admin')

@section('page-title', '店舗作成')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @include('admin.partials._errors')

                <form method="POST" action="{{ route('admin.locations.store') }}">
                    @csrf
                    @include('admin.locations._form')
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">作成</button>
                        <a href="{{ route('admin.locations.index') }}" class="btn btn-outline-secondary">キャンセル</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
