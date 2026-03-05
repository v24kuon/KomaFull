@php
    $model = $additionalItem ?? null;
@endphp

<div class="row mb-3">
    <div class="col-md-6">
        <label for="code" class="form-label">コード <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $model?->code) }}" required>
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="label_name" class="form-label">ラベル名 <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('label_name') is-invalid @enderror" id="label_name" name="label_name" value="{{ old('label_name', $model?->label_name) }}" required>
        @error('label_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label">項目種別 <span class="text-danger">*</span></label>
        <p class="form-control-plaintext mb-0">会員プロフィール</p>
        <input type="hidden" name="additional_item_type" value="member_profile">
        @error('additional_item_type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="input_type" class="form-label">入力形式 <span class="text-danger">*</span></label>
        <select class="form-select @error('input_type') is-invalid @enderror" id="input_type" name="input_type" required>
            <option value="text" @selected(old('input_type', $model?->input_type) === 'text')>テキスト</option>
            <option value="number" @selected(old('input_type', $model?->input_type) === 'number')>数値</option>
            <option value="select" @selected(old('input_type', $model?->input_type) === 'select')>セレクト</option>
            <option value="checkbox" @selected(old('input_type', $model?->input_type) === 'checkbox')>チェックボックス</option>
        </select>
        @error('input_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="digits" class="form-label">桁数</label>
        <input type="number" class="form-control @error('digits') is-invalid @enderror" id="digits" name="digits" value="{{ old('digits', $model?->digits) }}" min="1">
        @error('digits') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-4">
    <label for="status" class="form-label">ステータス <span class="text-danger">*</span></label>
    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
        <option value="active" @selected(old('status', $model?->status) === 'active')>有効</option>
        <option value="inactive" @selected(old('status', $model?->status) === 'inactive')>無効</option>
    </select>
    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
