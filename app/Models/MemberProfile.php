<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberProfile extends Model
{
    /** @use HasFactory<\Database\Factories\MemberProfileFactory> */
    use HasFactory;

    public const STATUS_PROVISIONAL = 'provisional';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * verified 配下・またはメール認証済みで member_profiles が無い場合のフラッシュ。
     * この状態では「メール認証後に自動作成」を再トリガーできないため、問い合わせ導線を示す。
     */
    public const FLASH_ERROR_MISSING_PROFILE_VERIFIED = '会員プロフィールがまだありません。自動作成に失敗している可能性があります。お問い合わせページからご連絡をお願いします。';

    /**
     * メール未認証のまま member_profiles が無い場合のフラッシュ（verified 外ルートなど）。
     */
    public const FLASH_ERROR_MISSING_PROFILE_UNVERIFIED = '会員プロフィールがまだありません。メール認証完了後に自動で作成されます。';

    protected $table = 'member_profiles';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'code',
        'member_status',
        'tel',
        'birth_date',
        'activated_at',
        'withdrawn_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'activated_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<MemberAdditionalItemValue, self>
     */
    public function additionalItemValues(): HasMany
    {
        return $this->hasMany(MemberAdditionalItemValue::class);
    }
}
