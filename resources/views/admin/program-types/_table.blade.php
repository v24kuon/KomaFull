<table class="table table-hover mb-0">
    <thead class="table-light">
        <tr>
            <th>コード</th>
            <th>名前</th>
            <th>表示順</th>
            <th>ステータス</th>
            <th class="text-end">操作</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($programTypes as $programType)
            <tr id="program-type-row-{{ $programType->id }}">
                <td>{{ $programType->code }}</td>
                <td>{{ $programType->name }}</td>
                <td>{{ $programType->sort_order }}</td>
                <td>
                    <span class="badge {{ $programType->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $programType->status === 'active' ? '有効' : '無効' }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.program-types.edit', $programType) }}" class="btn btn-sm btn-outline-primary">編集</a>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        hx-delete="{{ route('admin.program-types.destroy', $programType) }}"
                        hx-target="#program-type-row-{{ $programType->id }}"
                        hx-swap="outerHTML swap:300ms"
                        hx-confirm="「{{ $programType->name }}」を削除しますか？"
                    >削除</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-body-secondary py-4">プログラム種別が登録されていません。</td>
            </tr>
        @endforelse
    </tbody>
</table>
