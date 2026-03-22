<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Mail\ContactInquiryMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * お問い合わせフォームを表示する。
     *
     * 前提: ゲスト・会員いずれも利用可能。
     * 更新方針: 送信先は `config('mail.contact_to')`（config/mail.php で from.address へフォールバック済み）。
     */
    public function create(): View
    {
        return view('pages.contact.create');
    }

    /**
     * お問い合わせを受け付け、運営宛にメールを送る。
     *
     * 前提: StoreContactRequest で検証済みであること。
     * 更新方針: 送信失敗時は例外をそのまま伝播し、ログドライバ等で調査可能にする。
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Mail::to(config('mail.contact_to'))->send(new ContactInquiryMail(
            name: $validated['name'],
            email: $validated['email'],
            phone: $validated['phone'] ?? null,
            inquiryType: $validated['inquiry_type'],
            inquiryTypeLabel: StoreContactRequest::labelFor($validated['inquiry_type']),
            body: $validated['body'],
        ));

        return redirect()
            ->route('contact.create')
            ->with('status', 'お問い合わせを受け付けました。内容を確認のうえ、必要に応じてご返信いたします。');
    }
}
