@php
    $dayLabels = [
        0 => '日',
        1 => '月',
        2 => '火',
        3 => '水',
        4 => '木',
        5 => '金',
        6 => '土',
    ];
@endphp

<table class="table table-hover mb-0">
    <thead class="table-light">
        <tr>
            <th>プログラム</th>
            <th>店舗</th>
            <th>担当</th>
            <th>繰り返し</th>
            <th>有効期間</th>
            <th>開始時刻</th>
            <th>定員</th>
            <th>ステータス</th>
            <th class="text-end">操作</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($programRepetitionRules as $programRepetitionRule)
            <tr id="program-repetition-rule-row-{{ $programRepetitionRule->id }}">
                <td>{{ $programRepetitionRule->program->name }}</td>
                <td>{{ $programRepetitionRule->location->name }}</td>
                <td>{{ $programRepetitionRule->staff->name }}</td>
                <td>
                    @if ($programRepetitionRule->cycle_type === 'daily')
                        毎日
                    @else
                        毎週（{{ $dayLabels[$programRepetitionRule->day_of_week] ?? '?' }}）
                    @endif
                </td>
                <td>{{ $programRepetitionRule->start_date->format('Y-m-d') }} - {{ $programRepetitionRule->end_date->format('Y-m-d') }}</td>
                <td>{{ substr((string) $programRepetitionRule->start_time, 0, 8) }}</td>
                <td>通常 {{ $programRepetitionRule->capacity }} / 体験 {{ $programRepetitionRule->trial_capacity }}</td>
                <td>
                    @if ($programRepetitionRule->status === 'active')
                        <span class="badge bg-success">有効</span>
                    @elseif ($programRepetitionRule->status === 'inactive')
                        <span class="badge bg-secondary">無効</span>
                    @else
                        <span class="badge bg-warning text-dark">不正</span>
                    @endif
                </td>
                <td class="text-end">
                    <div class="d-inline-flex gap-2">
                        <a href="{{ route('admin.program-repetition-rules.edit', $programRepetitionRule) }}" class="btn btn-sm btn-outline-primary">編集</a>
                        <form method="POST" action="{{ route('admin.program-repetition-rules.generate', $programRepetitionRule) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success">1件生成</button>
                        </form>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            hx-delete="{{ route('admin.program-repetition-rules.destroy', $programRepetitionRule) }}"
                            hx-target="#program-repetition-rule-row-{{ $programRepetitionRule->id }}"
                            hx-swap="outerHTML swap:300ms"
                            hx-confirm="この繰り返し設定を削除しますか？"
                        >削除</button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center text-body-secondary py-4">繰り返し設定が登録されていません。</td>
            </tr>
        @endforelse
    </tbody>
</table>
