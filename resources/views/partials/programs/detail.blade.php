<article class="p-programs__detail">
    <header class="mb-3">
        <p class="small text-secondary mb-1">
            {{ $program->category?->name ?? '—' }}
            @if ($program->programType)
                <span class="text-muted"> · {{ $program->programType->name }}</span>
            @endif
        </p>
        <h2 class="h4 mb-0">{{ $program->name }}</h2>
    </header>

    <dl class="row small mb-4">
        <dt class="col-sm-4 col-md-3 text-secondary">所要時間</dt>
        <dd class="col-sm-8 col-md-9">{{ $program->duration_minutes }} 分</dd>
        @if ($program->level)
            <dt class="col-sm-4 col-md-3 text-secondary">レベル</dt>
            <dd class="col-sm-8 col-md-9 text-capitalize">{{ $program->level }}</dd>
        @endif
        <dt class="col-sm-4 col-md-3 text-secondary">料金（税込目安）</dt>
        <dd class="col-sm-8 col-md-9">&yen;{{ number_format($program->price) }}</dd>
        <dt class="col-sm-4 col-md-3 text-secondary">チケット / ポイント</dt>
        <dd class="col-sm-8 col-md-9">{{ $program->ticket_cost }} 枚 / {{ $program->point_cost }} pt</dd>
    </dl>

    <section class="mb-3" aria-labelledby="program-overview-heading">
        <h3 id="program-overview-heading" class="h6">概要</h3>
        <div class="text-secondary">
            @if ($program->overview !== null && $program->overview !== '')
                {!! nl2br(e($program->overview)) !!}
            @else
                <p class="mb-0">概要は準備中です。</p>
            @endif
        </div>
    </section>

    <section aria-labelledby="program-detail-heading">
        <h3 id="program-detail-heading" class="h6">詳細</h3>
        <div class="text-secondary">
            @if ($program->detail !== null && $program->detail !== '')
                {!! nl2br(e($program->detail)) !!}
            @else
                <p class="mb-0">詳細は準備中です。</p>
            @endif
        </div>
    </section>
</article>
