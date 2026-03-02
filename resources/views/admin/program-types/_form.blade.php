@php
    $model = $programType ?? null;
@endphp

<div class="mb-3">
    <label for="code" class="form-label">コード <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $model?->code) }}" required>
    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="name" class="form-label">名前 <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $model?->name) }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="sort_order" class="form-label">表示順 <span class="text-danger">*</span></label>
    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $model?->sort_order ?? 0) }}" min="0" required>
    @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-4">
    <label for="status" class="form-label">ステータス <span class="text-danger">*</span></label>
    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
        <option value="active" @selected(old('status', $model?->status) === 'active')>有効</option>
        <option value="inactive" @selected(old('status', $model?->status) === 'inactive')>無効</option>
    </select>
    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
