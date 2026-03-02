<table class="table table-hover mb-0">
    <thead class="table-light">
        <tr>
            <th>コード</th>
            <th>ラベル名</th>
            <th>入力形式</th>
            <th>桁数</th>
            <th>ステータス</th>
            <th class="text-end">操作</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($additionalItems as $item)
            <tr id="additional-item-row-{{ $item->id }}">
                <td>{{ $item->code }}</td>
                <td>{{ $item->label_name }}</td>
                <td>{{ $item->input_type }}</td>
                <td>{{ $item->digits ?? '-' }}</td>
                <td>
                    <span class="badge {{ $item->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $item->status === 'active' ? '有効' : '無効' }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.additional-items.edit', $item) }}" class="btn btn-sm btn-outline-primary">編集</a>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        hx-delete="{{ route('admin.additional-items.destroy', $item) }}"
                        hx-target="#additional-item-row-{{ $item->id }}"
                        hx-swap="outerHTML swap:300ms"
                        hx-confirm="「{{ $item->label_name }}」を削除しますか？"
                    >削除</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-body-secondary py-4">追加項目が登録されていません。</td>
            </tr>
        @endforelse
    </tbody>
</table>
