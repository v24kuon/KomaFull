<table class="table table-hover mb-0">
    <thead class="table-light">
        <tr>
            <th>コード</th>
            <th>名前</th>
            <th>住所</th>
            <th>電話番号</th>
            <th>ステータス</th>
            <th class="text-end">操作</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($locations as $location)
            <tr id="location-row-{{ $location->id }}">
                <td>{{ $location->code }}</td>
                <td>{{ $location->name }}</td>
                <td>{{ $location->address ?? '-' }}</td>
                <td>{{ $location->tel ?? '-' }}</td>
                <td>
                    <span class="badge {{ $location->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $location->status === 'active' ? '有効' : '無効' }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.locations.edit', $location) }}" class="btn btn-sm btn-outline-primary">編集</a>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        hx-delete="{{ route('admin.locations.destroy', $location) }}"
                        hx-target="#location-row-{{ $location->id }}"
                        hx-swap="outerHTML swap:300ms"
                        hx-confirm="「{{ $location->name }}」を削除しますか？"
                    >削除</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-body-secondary py-4">店舗が登録されていません。</td>
            </tr>
        @endforelse
    </tbody>
</table>
