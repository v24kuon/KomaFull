@extends('layouts.member')

@section('title', 'プロフィール')

@section('content')
<div class="p-member-profile">
    <header class="mb-4">
        <h1 class="h3 mb-1">プロフィール</h1>
        <p class="text-secondary mb-0">お名前・連絡先・追加項目を編集できます。</p>
    </header>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <x-ui.form-errors />

            <form method="POST" action="{{ route('member.profile.update') }}" x-data="submitState()" x-on:submit="startSubmitting($event)">
                @csrf
                @method('PUT')

                <p class="small text-secondary mb-3">会員番号 <span class="font-monospace">{{ $memberProfile->code }}</span></p>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">お名前 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
                        <x-ui.field-error field="name" />
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">メールアドレス</label>
                        <input type="email" class="form-control bg-light" id="email" value="{{ $user->email }}" readonly disabled autocomplete="email">
                        <p class="form-text small mb-0">メールアドレスの変更は別メニューから行います。</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="tel" class="form-label">電話番号</label>
                        <input type="text" class="form-control @error('tel') is-invalid @enderror" id="tel" name="tel" value="{{ old('tel', $memberProfile->tel) }}" autocomplete="tel">
                        <x-ui.field-error field="tel" />
                    </div>
                    <div class="col-md-6">
                        <label for="birth_date" class="form-label">生年月日</label>
                        <input type="date" class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date" value="{{ old('birth_date', $memberProfile->birth_date?->format('Y-m-d')) }}">
                        <x-ui.field-error field="birth_date" />
                    </div>
                </div>

                @if ($additionalItems->isNotEmpty())
                    <hr class="my-4">
                    <h2 class="h6 mb-3">追加項目</h2>

                    @foreach ($additionalItems as $item)
                        @php
                            $value = $valuesByItemId->get($item->id);
                            $oldVal = old('additional_items.'.$item->id);
                        @endphp

                        <div class="mb-1">
                            @if ($item->input_type === 'text')
                                <label for="ai_{{ $item->id }}" class="form-label">{{ $item->label_name }}</label>
                                <input type="text" class="form-control @error('additional_items.'.$item->id) is-invalid @enderror" id="ai_{{ $item->id }}" name="additional_items[{{ $item->id }}]" value="{{ $oldVal !== null ? $oldVal : ($value?->value) }}">
                            @elseif ($item->input_type === 'number')
                                <label for="ai_{{ $item->id }}" class="form-label">{{ $item->label_name }}</label>
                                <input type="number" class="form-control @error('additional_items.'.$item->id) is-invalid @enderror" id="ai_{{ $item->id }}" name="additional_items[{{ $item->id }}]" value="{{ $oldVal !== null ? $oldVal : ($value?->value) }}" min="0">
                            @elseif ($item->input_type === 'select')
                                <label for="ai_{{ $item->id }}" class="form-label">{{ $item->label_name }}</label>
                                <select class="form-select @error('additional_items.'.$item->id) is-invalid @enderror" id="ai_{{ $item->id }}" name="additional_items[{{ $item->id }}]">
                                    <option value="">選択してください</option>
                                    @foreach ($item->select_options ?? [] as $opt)
                                        @php
                                            $optStr = is_string($opt) ? $opt : (string) $opt;
                                            $current = $oldVal !== null ? $oldVal : ($value?->value);
                                        @endphp
                                        <option value="{{ $optStr }}" @selected($current === $optStr)>{{ $optStr }}</option>
                                    @endforeach
                                </select>
                            @elseif ($item->input_type === 'checkbox')
                                @php
                                    $checked = $oldVal !== null ? filter_var($oldVal, FILTER_VALIDATE_BOOLEAN) : (($value?->value ?? '0') === '1');
                                @endphp
                                <div class="form-check">
                                    <input type="hidden" name="additional_items[{{ $item->id }}]" value="0">
                                    <input type="checkbox" class="form-check-input @error('additional_items.'.$item->id) is-invalid @enderror" id="ai_{{ $item->id }}" name="additional_items[{{ $item->id }}]" value="1" @checked($checked)>
                                    <label class="form-check-label" for="ai_{{ $item->id }}">{{ $item->label_name }}</label>
                                </div>
                            @endif
                            <x-ui.field-error field="additional_items.{{ $item->id }}" />
                        </div>
                    @endforeach
                @endif

                <div class="d-flex gap-2 mt-4">
                    <x-ui.submit-button>保存する</x-ui.submit-button>
                    <a href="{{ route('member.dashboard') }}" class="btn btn-outline-secondary">マイページに戻る</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
