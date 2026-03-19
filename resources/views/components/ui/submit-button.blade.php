@props([
    'loading' => '送信中...',
    'type' => 'submit',
])

@php
    $defaultClass = trim((string) $attributes->get('class', '')) === '' ? 'btn btn-primary' : '';
@endphp

<button
    type="{{ $type }}"
    x-bind:disabled="submitting"
    x-bind:aria-busy="submitting ? 'true' : 'false'"
    {{ $attributes->merge(['class' => $defaultClass]) }}
>
    <span x-bind:class="submitting ? 'd-none' : 'd-inline'">{{ $slot }}</span>
    <span class="d-none" x-bind:class="{ 'd-none': !submitting, 'd-inline-flex align-items-center': submitting }">
        <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
        <span>{{ $loading }}</span>
    </span>
</button>