@extends('layouts.member')

@section('title', '退会')

@section('content')
<div class="p-member-settings-withdraw">
    <header class="mb-4">
        <nav aria-label="パンくず" class="mb-2">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('member.settings.index') }}">会員設定</a></li>
                <li class="breadcrumb-item active" aria-current="page">退会</li>
            </ol>
        </nav>
        <h1 class="h3 mb-1">退会手続き</h1>
        <p class="text-secondary mb-0">退会するとマイページはご利用いただけなくなります。サブスクリプションがある場合は解約処理を行います。</p>
    </header>

    <div class="alert alert-warning" role="alert">
        <strong>ご注意:</strong> この操作は取り消せません。必要な情報は事前に控えてください。
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <x-ui.form-errors />

            <form method="POST" action="{{ route('member.settings.withdraw.destroy') }}" x-data="submitState()" x-on:submit="startSubmitting($event)">
                @csrf

                <div class="mb-3">
                    <label for="current_password" class="form-label">現在のパスワード <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required autocomplete="current-password">
                    <x-ui.field-error field="current_password" />
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input @error('withdrawal_confirmed') is-invalid @enderror" id="withdrawal_confirmed" name="withdrawal_confirmed" value="1" {{ old('withdrawal_confirmed') ? 'checked' : '' }}>
                    <label class="form-check-label" for="withdrawal_confirmed">退会する内容を理解し、手続きを進める</label>
                    <x-ui.field-error field="withdrawal_confirmed" />
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-danger">退会する</button>
                    <a class="btn btn-outline-secondary" href="{{ route('member.settings.index') }}">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
