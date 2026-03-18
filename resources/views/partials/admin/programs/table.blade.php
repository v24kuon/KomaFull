<table class="table table-hover mb-0">
    <thead class="table-light">
        <tr>
            <th>コード</th>
            <th>名前</th>
            <th>カテゴリ</th>
            <th>種別</th>
            <th>時間</th>
            <th>料金</th>
            <th>ステータス</th>
            <th class="text-end">操作</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($programs as $program)
            <tr id="program-row-{{ $program->id }}">
                <td>{{ $program->code }}</td>
                <td>{{ $program->name }}</td>
                <td>{{ $program->category?->name }}</td>
                <td>{{ $program->programType?->name }}</td>
                <td>{{ $program->duration_minutes }}分</td>
                <td>¥{{ number_format($program->price) }}</td>
                <td>
                    <span class="badge {{ $program->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $program->status === 'active' ? '有効' : '無効' }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.programs.edit', $program) }}" class="btn btn-sm btn-outline-primary">編集</a>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        hx-delete="{{ route('admin.programs.destroy', $program) }}"
                        hx-target="#program-row-{{ $program->id }}"
                        hx-swap="outerHTML swap:300ms"
                        hx-confirm="「{{ $program->name }}」を削除しますか？"
                    >削除</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-body-secondary py-4">プログラムが登録されていません。</td>
            </tr>
        @endforelse
    </tbody>
</table>
