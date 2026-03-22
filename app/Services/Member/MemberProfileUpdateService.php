<?php

namespace App\Services\Member;

use App\Models\AdditionalItem;
use App\Models\MemberAdditionalItemValue;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;

class MemberProfileUpdateService
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    /**
     * 会員の表示名・プロフィール・追加項目を単一トランザクションで更新する。
     *
     * 前提: 認証済みユーザーに紐づく member_profiles が存在すること。
     * 更新方針: 追加項目はマスタ（有効な member_profile 項目）を正とし、空の任意項目は値行を削除する。チェックボックスは常に 0/1 を保持する。
     *
     * @param  array{name: string, tel?: string|null, birth_date?: string|null, additional_items?: array<int|string, mixed>}  $validated
     */
    public function update(User $user, array $validated): void
    {
        $this->connection->transaction(function () use ($user, $validated): void {
            $user->fill([
                'name' => $validated['name'],
            ])->save();

            $profile = $user->memberProfile;
            if (! $profile instanceof MemberProfile) {
                return;
            }

            $tel = $validated['tel'] ?? null;
            $birthDate = $validated['birth_date'] ?? null;

            $profile->update([
                'tel' => is_string($tel) && $tel !== '' ? $tel : null,
                'birth_date' => is_string($birthDate) && $birthDate !== '' ? $birthDate : null,
            ]);

            $items = AdditionalItem::query()
                ->where('additional_item_type', AdditionalItem::TYPE_MEMBER_PROFILE)
                ->where('status', AdditionalItem::STATUS_ACTIVE)
                ->orderBy('id')
                ->get();

            $payload = $validated['additional_items'] ?? [];

            foreach ($items as $item) {
                $raw = $payload[$item->id] ?? $payload[(string) $item->id] ?? null;
                $this->syncAdditionalItemValue($profile, $item, $raw);
            }
        });
    }

    /**
     * 1 件の追加項目マスタに対し、会員プロフィール上の `member_additional_item_values` を同期する。
     *
     * 前提: `$profile` は更新対象の会員プロフィール、`$item` は `update()` 側で取得した有効マスタ行であること。
     * 更新方針: チェックボックスは未送信を false とみなし、常に値行を upsert して `'0'` / `'1'` を保持する。
     * それ以外の入力型では、`$raw` が null または空文字のときは任意項目として値行を削除し、値があるときは文字列化して upsert する。
     *
     * 副作用: `member_additional_item_values` への `INSERT` / `UPDATE`（updateOrCreate）または該当行の `DELETE` を行う。呼び出し元のトランザクション内で実行される。
     */
    private function syncAdditionalItemValue(MemberProfile $profile, AdditionalItem $item, mixed $raw): void
    {
        if ($item->input_type === 'checkbox') {
            $checked = $raw === true || $raw === 1 || $raw === '1';
            $value = $checked ? '1' : '0';

            MemberAdditionalItemValue::query()->updateOrCreate(
                [
                    'member_profile_id' => $profile->id,
                    'additional_item_id' => $item->id,
                ],
                ['value' => $value]
            );

            return;
        }

        if ($raw === null || $raw === '') {
            MemberAdditionalItemValue::query()->where([
                'member_profile_id' => $profile->id,
                'additional_item_id' => $item->id,
            ])->delete();

            return;
        }

        $value = is_scalar($raw) ? (string) $raw : '';

        MemberAdditionalItemValue::query()->updateOrCreate(
            [
                'member_profile_id' => $profile->id,
                'additional_item_id' => $item->id,
            ],
            ['value' => $value]
        );
    }
}
