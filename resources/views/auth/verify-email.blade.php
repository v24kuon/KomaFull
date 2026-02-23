@extends('layouts.app')

@section('title', 'メール認証')

@section('content')
<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-sm-10 col-md-8 col-lg-5 col-xl-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 text-center mb-4">メールアドレスの確認</h1>

                    <p class="text-center mb-3">
                        ご登録いただいたメールアドレスに確認メールを送信しました。メール内のリンクをクリックして認証を完了してください。
                    </p>

                    @if (session('status') == 'verification-link-sent')
                        <div class="alert alert-success" role="alert">
                            確認メールを再送しました。
                        </div>
                    @endif

                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">確認メールを再送する</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <div class="text-center">
                            <button type="submit" class="btn btn-link text-decoration-none">ログアウト</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
