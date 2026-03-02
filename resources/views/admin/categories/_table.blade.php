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
        @forelse ($categories as $category)
            <tr id="category-row-{{ $category->id }}">
                <td>{{ $category->code }}</td>
                <td>{{ $category->name }}</td>
                <td>{{ $category->sort_order }}</td>
                <td>
                    <span class="badge {{ $category->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $category->status === 'active' ? '有効' : '無効' }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">編集</a>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        hx-delete="{{ route('admin.categories.destroy', $category) }}"
                        hx-target="#category-row-{{ $category->id }}"
                        hx-swap="outerHTML swap:300ms"
                        hx-confirm="「{{ $category->name }}」を削除しますか？"
                    >削除</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-body-secondary py-4">カテゴリが登録されていません。</td>
            </tr>
        @endforelse
    </tbody>
</table>
