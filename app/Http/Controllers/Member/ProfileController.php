<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateMemberProfileRequest;
use App\Models\AdditionalItem;
use App\Models\User;
use App\Services\Member\MemberProfileUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly MemberProfileUpdateService $memberProfileUpdateService,
    ) {}

    /**
     * 会員プロフィール編集フォームを表示する。
     *
     * 前提: 認証済みユーザーに紐づく member_profiles が存在すること。
     * 更新方針: 読み取り専用で、DB更新は行わない。
     */
    public function edit(): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $memberProfile = $user->memberProfile;
        if ($memberProfile === null) {
            return redirect()
                ->route('member.dashboard')
                ->with('error', '会員プロフィールがまだありません。メール認証完了後に自動で作成されます。');
        }

        $additionalItems = AdditionalItem::query()
            ->where('additional_item_type', AdditionalItem::TYPE_MEMBER_PROFILE)
            ->where('status', AdditionalItem::STATUS_ACTIVE)
            ->orderBy('id')
            ->get();

        $valuesByItemId = $memberProfile->additionalItemValues()->get()->keyBy('additional_item_id');

        return view('pages.member.profile.edit', [
            'user' => $user,
            'memberProfile' => $memberProfile,
            'additionalItems' => $additionalItems,
            'valuesByItemId' => $valuesByItemId,
        ]);
    }

    /**
     * 会員プロフィールを更新する。
     *
     * 前提: UpdateMemberProfileRequest で検証済みであること。
     * 更新方針: MemberProfileUpdateService に委譲し、同一トランザクションで会員名・プロフィール・追加項目を保存する。
     */
    public function update(UpdateMemberProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->memberProfile === null) {
            return redirect()
                ->route('member.dashboard')
                ->with('error', '会員プロフィールがまだありません。メール認証完了後に自動で作成されます。');
        }

        $this->memberProfileUpdateService->update($user, $request->validated());

        return redirect()
            ->route('member.profile.edit')
            ->with('success', 'プロフィールを更新しました。');
    }
}
