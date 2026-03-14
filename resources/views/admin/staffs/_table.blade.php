<table class="table table-hover mb-0">
    <thead class="table-light">
        <tr>
            <th>コード</th>
            <th>名前</th>
            <th>役割</th>
            <th>専門</th>
            <th>ステータス</th>
            <th class="text-end">操作</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($staffs as $staff)
            <tr id="staff-row-{{ $staff->id }}">
                <td>{{ $staff->code }}</td>
                <td>{{ $staff->name }}</td>
                <td>{{ $staff->role ?? '-' }}</td>
                <td>{{ $staff->main_expertise ?? '-' }}</td>
                <td>
                    <span class="badge {{ $staff->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $staff->status === 'active' ? '有効' : '無効' }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.staffs.edit', $staff) }}" class="btn btn-sm btn-outline-primary">編集</a>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        hx-delete="{{ route('admin.staffs.destroy', $staff) }}"
                        hx-target="#staff-row-{{ $staff->id }}"
                        hx-swap="outerHTML swap:300ms"
                        hx-confirm="「{{ $staff->name }}」を削除しますか？"
                    >削除</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-body-secondary py-4">スタッフが登録されていません。</td>
            </tr>
        @endforelse
    </tbody>
</table>
