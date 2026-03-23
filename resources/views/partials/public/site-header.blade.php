<header class="c-site-header border-bottom bg-white shadow-sm sticky-top">
    <nav class="navbar navbar-expand-lg navbar-light" aria-label="メイン">
        <div class="container">
            <a class="navbar-brand fw-bold c-site-header__brand" href="{{ route('home') }}">{{ config('app.name', 'KomaFull') }}</a>
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#c-site-header-nav"
                aria-controls="c-site-header-nav"
                aria-expanded="false"
                aria-label="ナビゲーションを開く"
            >
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="c-site-header-nav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('programs.*')) active @endif" href="{{ route('programs.index') }}">プログラム</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('schedule.*')) active @endif" href="{{ route('schedule.index') }}">開催枠</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('stores.*')) active @endif" href="{{ route('stores.index') }}">店舗</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('contact.*')) active @endif" href="{{ route('contact.create') }}">お問い合わせ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('legal.tokushoho')) active @endif" href="{{ route('legal.tokushoho') }}">特定商取引法</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto gap-lg-2">
                    @guest
                        @if (Route::has('login'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">ログイン</a>
                            </li>
                        @endif
                        @if (Route::has('register'))
                            <li class="nav-item">
                                <a class="btn btn-primary btn-sm px-3 mt-1 mt-lg-0" href="{{ route('register') }}">会員登録</a>
                            </li>
                        @endif
                    @else
                        @can('access-admin')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('admin.dashboard') }}">管理画面</a>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('member.dashboard') }}">マイページ</a>
                            </li>
                        @endcan
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm mt-1 mt-lg-0">ログアウト</button>
                            </form>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>
</header>
