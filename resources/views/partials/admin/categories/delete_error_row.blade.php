<tr id="category-row-{{ $category->id }}" class="table-warning">
    <td colspan="5">
        <div class="text-danger" role="alert">
            <div class="fw-semibold">{{ $category->name }} は削除できません。</div>
            <div class="small">{{ $message }}</div>
        </div>
    </td>
</tr>
