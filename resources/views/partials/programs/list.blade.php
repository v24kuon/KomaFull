<div class="row g-4" id="program-list">
    @forelse ($programs as $program)
        <div class="col-12 col-md-6 col-xl-4">
            <article class="card h-100 border-0 shadow-sm p-programs__card">
                <div class="card-body d-flex flex-column">
                    <p class="small text-secondary mb-2">
                        {{ $program->category?->name ?? '—' }}
                        @if ($program->programType)
                            <span class="text-muted"> · {{ $program->programType->name }}</span>
                        @endif
                    </p>
                    <h2 class="h5 card-title">{{ $program->name }}</h2>
                    <p class="card-text text-secondary small flex-grow-1">
                        {{ $program->overview !== null && $program->overview !== '' ? \Illuminate\Support\Str::limit($program->overview, 140) : '概要は準備中です。' }}
                    </p>
                    <div class="d-flex flex-wrap gap-2 align-items-center mt-auto pt-2">
                        <span class="badge rounded-pill text-bg-light border">
                            {{ $program->duration_minutes }} 分
                        </span>
                        @if ($program->level)
                            <span class="badge rounded-pill text-bg-light border text-capitalize">{{ $program->level }}</span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button
                            type="button"
                            class="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#programDetailModal"
                            hx-get="{{ route('programs.show', $program) }}"
                            hx-target="#programModalBody"
                            hx-swap="innerHTML"
                        >
                            詳細を見る
                        </button>
                        <a href="{{ route('programs.show', $program) }}" class="btn btn-outline-secondary btn-sm">
                            別ページで開く
                        </a>
                    </div>
                </div>
            </article>
        </div>
    @empty
        <div class="col-12">
            <p class="text-secondary mb-0" role="status">現在表示できるプログラムはありません。</p>
        </div>
    @endforelse
</div>
