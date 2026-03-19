@php
    $model = $location ?? null;
@endphp

<div class="row mb-3">
    <div class="col-md-6">
        <label for="code" class="form-label">コード <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $model?->code) }}" required>
        <x-ui.field-error field="code" />
    </div>
    <div class="col-md-6">
        <label for="name" class="form-label">名前 <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $model?->name) }}" required>
        <x-ui.field-error field="name" />
    </div>
</div>

<div class="mb-3">
    <label for="address" class="form-label">住所</label>
    <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $model?->address) }}">
    <x-ui.field-error field="address" />
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="tel" class="form-label">電話番号</label>
        <input type="tel" class="form-control @error('tel') is-invalid @enderror" id="tel" name="tel" value="{{ old('tel', $model?->tel) }}">
        <x-ui.field-error field="tel" />
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">メールアドレス</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $model?->email) }}">
        <x-ui.field-error field="email" />
    </div>
</div>

<div class="mb-3">
    <label for="description" class="form-label">説明</label>
    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $model?->description) }}</textarea>
    <x-ui.field-error field="description" />
</div>

<div class="mb-4">
    <label for="status" class="form-label">ステータス <span class="text-danger">*</span></label>
    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
        <option value="{{ \App\Models\Location::STATUS_ACTIVE }}" @selected(old('status', $model?->status) === \App\Models\Location::STATUS_ACTIVE)>有効</option>
        <option value="{{ \App\Models\Location::STATUS_INACTIVE }}" @selected(old('status', $model?->status) === \App\Models\Location::STATUS_INACTIVE)>無効</option>
    </select>
    <x-ui.field-error field="status" />
</div>
