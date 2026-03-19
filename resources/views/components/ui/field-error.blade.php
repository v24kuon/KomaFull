@props([
    'field',
    'bag' => null,
    'feedbackClass' => 'invalid-feedback',
])

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $message = $viewErrors->getBag($bag ?? 'default')->first($field);
@endphp

@if ($message !== '')
    <div {{ $attributes->merge(['class' => $feedbackClass]) }} role="alert">{{ $message }}</div>
@endif