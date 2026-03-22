@use('App\Enums\AdditionalItemInputType')

@php
    $model = $additionalItem ?? null;
    $linesDefault = old('select_options_lines');
    if ($linesDefault === null && $model !== null && is_array($model->select_options)) {
        $linesDefault = implode("\n", $model->select_options);
    }
    $linesDefault = is_string($linesDefault) ? $linesDefault : '';
@endphp

<div x-data="additionalItemForm()">
<div class="row mb-3">
    <div class="col-md-6">
        <label for="code" class="form-label">コード <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $model?->code) }}" required>
        <x-ui.field-error field="code" />
    </div>
    <div class="col-md-6">
        <label for="label_name" class="form-label">ラベル名 <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('label_name') is-invalid @enderror" id="label_name" name="label_name" value="{{ old('label_name', $model?->label_name) }}" required>
        <x-ui.field-error field="label_name" />
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label">項目種別 <span class="text-danger">*</span></label>
        <p class="form-control-plaintext mb-0">会員プロフィール</p>
        <input type="hidden" name="additional_item_type" value="member_profile">
        <x-ui.field-error field="additional_item_type" feedback-class="d-block text-danger small mt-1" />
    </div>
    <div class="col-md-4">
        <label for="input_type" class="form-label">入力形式 <span class="text-danger">*</span></label>
        <select class="form-select @error('input_type') is-invalid @enderror" id="input_type" name="input_type" required>
            <option value="{{ AdditionalItemInputType::Text->value }}" @selected(old('input_type', $model?->input_type) === AdditionalItemInputType::Text->value)>テキスト</option>
            <option value="{{ AdditionalItemInputType::Number->value }}" @selected(old('input_type', $model?->input_type) === AdditionalItemInputType::Number->value)>数値</option>
            <option value="{{ AdditionalItemInputType::Select->value }}" @selected(old('input_type', $model?->input_type) === AdditionalItemInputType::Select->value)>セレクト</option>
            <option value="{{ AdditionalItemInputType::Checkbox->value }}" @selected(old('input_type', $model?->input_type) === AdditionalItemInputType::Checkbox->value)>チェックボックス</option>
        </select>
        <x-ui.field-error field="input_type" />
    </div>
    <div class="col-md-4">
        <label for="digits" class="form-label">桁数</label>
        <input type="number" class="form-control @error('digits') is-invalid @enderror" id="digits" name="digits" value="{{ old('digits', $model?->digits) }}" min="1">
        <x-ui.field-error field="digits" />
    </div>
</div>

<div class="row mb-3" x-show="inputType === '{{ AdditionalItemInputType::Select->value }}'">
    <div class="col-12">
        <label for="select_options_lines" class="form-label">セレクト候補（1行に1つ）</label>
        <textarea class="form-control @error('select_options') is-invalid @enderror" id="select_options_lines" name="select_options_lines" rows="4">{{ $linesDefault }}</textarea>
        <x-ui.field-error field="select_options" />
        <p class="form-text small mb-0">入力形式がセレクトのときのみ使用します。</p>
    </div>
</div>

<div class="mb-4">
    <label for="status" class="form-label">ステータス <span class="text-danger">*</span></label>
    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
        <option value="active" @selected(old('status', $model?->status) === 'active')>有効</option>
        <option value="inactive" @selected(old('status', $model?->status) === 'inactive')>無効</option>
    </select>
    <x-ui.field-error field="status" />
</div>
</div>
