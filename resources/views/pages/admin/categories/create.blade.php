@extends('layouts.admin')

@section('page-title', 'カテゴリ作成')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @include('partials.admin.errors')

                <form method="POST" action="{{ route('admin.categories.store') }}" x-data="submitState()" x-on:submit="startSubmitting($event)">
                    @csrf
                    @include('partials.admin.categories.form')
                    <div class="d-flex gap-2">
                        <x-ui.submit-button>作成</x-ui.submit-button>
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">キャンセル</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
