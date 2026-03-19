@php
    $model = $program ?? null;
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
    <div class="col-md-6">
        <label for="category_id" class="form-label">カテゴリ <span class="text-danger">*</span></label>
        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
            <option value="">選択してください</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $model?->category_id) == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <x-ui.field-error field="category_id" />
    </div>
    <div class="col-md-6">
        <label for="program_type_id" class="form-label">プログラム種別 <span class="text-danger">*</span></label>
        <select class="form-select @error('program_type_id') is-invalid @enderror" id="program_type_id" name="program_type_id" required>
            <option value="">選択してください</option>
            @foreach ($programTypes as $programType)
                <option value="{{ $programType->id }}" @selected(old('program_type_id', $model?->program_type_id) == $programType->id)>{{ $programType->name }}</option>
            @endforeach
        </select>
        <x-ui.field-error field="program_type_id" />
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label for="level" class="form-label">レベル</label>
        <input type="text" class="form-control @error('level') is-invalid @enderror" id="level" name="level" value="{{ old('level', $model?->level) }}">
        <x-ui.field-error field="level" />
    </div>
    <div class="col-md-4">
        <label for="duration_minutes" class="form-label">時間（分） <span class="text-danger">*</span></label>
        <input type="number" class="form-control @error('duration_minutes') is-invalid @enderror" id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes', $model?->duration_minutes ?? 60) }}" min="1" step="1" required>
        <x-ui.field-error field="duration_minutes" />
    </div>
    <div class="col-md-4">
        <label for="status" class="form-label">ステータス <span class="text-danger">*</span></label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            <option value="active" @selected(old('status', $model?->status) === 'active')>有効</option>
            <option value="inactive" @selected(old('status', $model?->status) === 'inactive')>無効</option>
        </select>
        <x-ui.field-error field="status" />
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label for="price" class="form-label">料金（円） <span class="text-danger">*</span></label>
        <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $model?->price ?? 0) }}" min="0" step="1" required>
        <x-ui.field-error field="price" />
    </div>
    <div class="col-md-4">
        <label for="point_cost" class="form-label">ポイントコスト <span class="text-danger">*</span></label>
        <input type="number" class="form-control @error('point_cost') is-invalid @enderror" id="point_cost" name="point_cost" value="{{ old('point_cost', $model?->point_cost ?? 0) }}" min="0" step="1" required>
        <x-ui.field-error field="point_cost" />
    </div>
    <div class="col-md-4">
        <label for="ticket_cost" class="form-label">回数券コスト <span class="text-danger">*</span></label>
        <input type="number" class="form-control @error('ticket_cost') is-invalid @enderror" id="ticket_cost" name="ticket_cost" value="{{ old('ticket_cost', $model?->ticket_cost ?? 0) }}" min="0" step="1" required>
        <x-ui.field-error field="ticket_cost" />
    </div>
</div>

<div class="mb-3">
    <label for="overview" class="form-label">概要</label>
    <textarea class="form-control @error('overview') is-invalid @enderror" id="overview" name="overview" rows="3">{{ old('overview', $model?->overview) }}</textarea>
    <x-ui.field-error field="overview" />
</div>

<div class="mb-4">
    <label for="detail" class="form-label">詳細</label>
    <textarea class="form-control @error('detail') is-invalid @enderror" id="detail" name="detail" rows="5">{{ old('detail', $model?->detail) }}</textarea>
    <x-ui.field-error field="detail" />
</div>
