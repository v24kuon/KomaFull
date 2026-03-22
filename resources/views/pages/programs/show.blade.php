@extends('layouts.app')

@section('title', config('app.name', 'KomaFull').' | '.$program->name)

@push('styles')
    <link rel="stylesheet" href="{{ v_asset('assets/css/pages/programs.css') }}">
@endpush

@section('content')
<div class="p-programs py-5">
    <div class="container p-programs__container">
        <nav aria-label="パンくず">
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('programs.index') }}">プログラム</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $program->name }}</li>
            </ol>
        </nav>

        @include('partials.programs.detail', ['program' => $program])
    </div>
</div>
@endsection
