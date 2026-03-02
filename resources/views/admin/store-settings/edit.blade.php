@extends('layouts.admin')

@section('page-title', '店舗設定')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @include('admin.partials._errors')

                <form method="POST" action="{{ route('admin.store-settings.update') }}">
                    @csrf
                    @method('PUT')

                    <h5 class="mb-3">表示ラベル</h5>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="program_label" class="form-label">プログラム表記 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('program_label') is-invalid @enderror" id="program_label" name="program_label" value="{{ old('program_label', $settings->program_label) }}" required>
                            @error('program_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="session_label" class="form-label">セッション表記 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('session_label') is-invalid @enderror" id="session_label" name="session_label" value="{{ old('session_label', $settings->session_label) }}" required>
                            @error('session_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="staff_label" class="form-label">スタッフ表記 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('staff_label') is-invalid @enderror" id="staff_label" name="staff_label" value="{{ old('staff_label', $settings->staff_label) }}" required>
                            @error('staff_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="location_label" class="form-label">店舗表記 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('location_label') is-invalid @enderror" id="location_label" name="location_label" value="{{ old('location_label', $settings->location_label) }}" required>
                            @error('location_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3">締切設定</h5>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="reserve_deadline_minutes" class="form-label">予約締切（分前） <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('reserve_deadline_minutes') is-invalid @enderror" id="reserve_deadline_minutes" name="reserve_deadline_minutes" value="{{ old('reserve_deadline_minutes', $settings->reserve_deadline_minutes) }}" min="0" required>
                            @error('reserve_deadline_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="cancel_deadline_minutes" class="form-label">キャンセル締切（分前） <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('cancel_deadline_minutes') is-invalid @enderror" id="cancel_deadline_minutes" name="cancel_deadline_minutes" value="{{ old('cancel_deadline_minutes', $settings->cancel_deadline_minutes) }}" min="0" required>
                            @error('cancel_deadline_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="withdrawal_deadline_days" class="form-label">退会猶予（日） <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('withdrawal_deadline_days') is-invalid @enderror" id="withdrawal_deadline_days" name="withdrawal_deadline_days" value="{{ old('withdrawal_deadline_days', $settings->withdrawal_deadline_days) }}" min="0" required>
                            @error('withdrawal_deadline_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">更新</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
