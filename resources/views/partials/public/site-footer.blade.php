<footer class="c-site-footer border-top mt-auto py-4 bg-body-tertiary">
    <div class="container">
        <nav aria-label="フッター" class="small">
            <ul class="list-inline mb-2">
                <li class="list-inline-item"><a href="{{ route('home') }}">ホーム</a></li>
                <li class="list-inline-item"><a href="{{ route('programs.index') }}">プログラム</a></li>
                <li class="list-inline-item"><a href="{{ route('schedule.index') }}">開催枠</a></li>
                <li class="list-inline-item"><a href="{{ route('stores.index') }}">店舗</a></li>
                <li class="list-inline-item"><a href="{{ route('contact.create') }}">お問い合わせ</a></li>
                <li class="list-inline-item"><a href="{{ route('legal.tokushoho') }}">特定商取引法に基づく表記</a></li>
            </ul>
            <p class="text-secondary mb-0">&copy; {{ date('Y') }} {{ config('app.name', 'KomaFull') }}</p>
        </nav>
    </div>
</footer>
