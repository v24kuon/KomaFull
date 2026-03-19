@php
    $model = $staff ?? null;
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

<div class="row mb-3">
    <div class="col-md-4">
        <label for="gender" class="form-label">性別</label>
        <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender">
            <option value="">未設定</option>
            <option value="male" @selected(old('gender', $model?->gender) === 'male')>男性</option>
            <option value="female" @selected(old('gender', $model?->gender) === 'female')>女性</option>
            <option value="other" @selected(old('gender', $model?->gender) === 'other')>その他</option>
        </select>
        <x-ui.field-error field="gender" />
    </div>
    <div class="col-md-4">
        <label for="birth_date" class="form-label">生年月日</label>
        <input type="date" class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date" value="{{ old('birth_date', $model?->birth_date?->format('Y-m-d')) }}">
        <x-ui.field-error field="birth_date" />
    </div>
    <div class="col-md-4">
        <label for="role" class="form-label">役割</label>
        <input type="text" class="form-control @error('role') is-invalid @enderror" id="role" name="role" value="{{ old('role', $model?->role) }}">
        <x-ui.field-error field="role" />
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="licence_skill" class="form-label">資格・スキル</label>
        <input type="text" class="form-control @error('licence_skill') is-invalid @enderror" id="licence_skill" name="licence_skill" value="{{ old('licence_skill', $model?->licence_skill) }}">
        <x-ui.field-error field="licence_skill" />
    </div>
    <div class="col-md-6">
        <label for="main_expertise" class="form-label">専門分野</label>
        <input type="text" class="form-control @error('main_expertise') is-invalid @enderror" id="main_expertise" name="main_expertise" value="{{ old('main_expertise', $model?->main_expertise) }}">
        <x-ui.field-error field="main_expertise" />
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
        <option value="active" @selected(old('status', $model?->status) === 'active')>有効</option>
        <option value="inactive" @selected(old('status', $model?->status) === 'inactive')>無効</option>
    </select>
    <x-ui.field-error field="status" />
</div>
