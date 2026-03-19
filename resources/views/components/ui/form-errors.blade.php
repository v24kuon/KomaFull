@props([
    'bag' => 'default',
])

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $errorBag = $viewErrors->getBag($bag);
@endphp

@if ($errorBag->any())
    <div {{ $attributes->merge(['class' => 'alert alert-danger']) }} role="alert">
        <ul class="mb-0">
            @foreach ($errorBag->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif