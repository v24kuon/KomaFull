@php
    $model = $programRepetitionRule ?? null;
@endphp

@include('partials.admin.errors')

<div class="alert alert-info" role="alert">
    毎週は 1 設定 = 1 曜日です。終了日は必須で、毎日ルールでは曜日を指定しません。
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label for="program_id" class="form-label">プログラム <span class="text-danger">*</span></label>
        <select class="form-select @error('program_id') is-invalid @enderror" id="program_id" name="program_id" required>
            <option value="">選択してください</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(old('program_id', $model?->program_id) == $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
        @error('program_id') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="location_id" class="form-label">店舗 <span class="text-danger">*</span></label>
        <select class="form-select @error('location_id') is-invalid @enderror" id="location_id" name="location_id" required>
            <option value="">選択してください</option>
            @foreach ($locations as $location)
                <option value="{{ $location->id }}" @selected(old('location_id', $model?->location_id) == $location->id)>{{ $location->name }}</option>
            @endforeach
        </select>
        @error('location_id') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="staff_id" class="form-label">担当スタッフ <span class="text-danger">*</span></label>
        <select class="form-select @error('staff_id') is-invalid @enderror" id="staff_id" name="staff_id" required>
            <option value="">選択してください</option>
            @foreach ($staffs as $staff)
                <option value="{{ $staff->id }}" @selected(old('staff_id', $model?->staff_id) == $staff->id)>{{ $staff->name }}</option>
            @endforeach
        </select>
        @error('staff_id') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label for="cycle_type" class="form-label">繰り返し種別 <span class="text-danger">*</span></label>
        <select class="form-select @error('cycle_type') is-invalid @enderror" id="cycle_type" name="cycle_type" required>
            <option value="daily" @selected(old('cycle_type', $model?->cycle_type) === 'daily')>毎日</option>
            <option value="weekly" @selected(old('cycle_type', $model?->cycle_type) === 'weekly')>毎週</option>
        </select>
        @error('cycle_type') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="day_of_week" class="form-label">曜日</label>
        <select class="form-select @error('day_of_week') is-invalid @enderror" id="day_of_week" name="day_of_week">
            <option value="">毎日の場合は未選択</option>
            <option value="0" @selected((string) old('day_of_week', $model?->day_of_week) === '0')>日</option>
            <option value="1" @selected((string) old('day_of_week', $model?->day_of_week) === '1')>月</option>
            <option value="2" @selected((string) old('day_of_week', $model?->day_of_week) === '2')>火</option>
            <option value="3" @selected((string) old('day_of_week', $model?->day_of_week) === '3')>水</option>
            <option value="4" @selected((string) old('day_of_week', $model?->day_of_week) === '4')>木</option>
            <option value="5" @selected((string) old('day_of_week', $model?->day_of_week) === '5')>金</option>
            <option value="6" @selected((string) old('day_of_week', $model?->day_of_week) === '6')>土</option>
        </select>
        <div class="form-text">週次ルールのときだけ選択してください。</div>
        @error('day_of_week') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="status" class="form-label">ステータス <span class="text-danger">*</span></label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            <option value="active" @selected(old('status', $model?->status) === 'active')>有効</option>
            <option value="inactive" @selected(old('status', $model?->status) === 'inactive')>無効</option>
        </select>
        @error('status') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label for="start_date" class="form-label">開始日 <span class="text-danger">*</span></label>
        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', $model?->start_date?->format('Y-m-d')) }}" required>
        @error('start_date') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="end_date" class="form-label">終了日 <span class="text-danger">*</span></label>
        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date', $model?->end_date?->format('Y-m-d')) }}" required>
        @error('end_date') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="start_time" class="form-label">開始時刻 <span class="text-danger">*</span></label>
        <input type="time" step="1" class="form-control @error('start_time') is-invalid @enderror" id="start_time" name="start_time" value="{{ old('start_time', $model?->start_time) }}" required>
        @error('start_time') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <label for="capacity" class="form-label">通常定員 <span class="text-danger">*</span></label>
        <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', $model?->capacity ?? 0) }}" min="0" step="1" required>
        @error('capacity') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="trial_capacity" class="form-label">体験定員 <span class="text-danger">*</span></label>
        <input type="number" class="form-control @error('trial_capacity') is-invalid @enderror" id="trial_capacity" name="trial_capacity" value="{{ old('trial_capacity', $model?->trial_capacity ?? 0) }}" min="0" step="1" required>
        @error('trial_capacity') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>
</div>
