<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class SharedFormUiComponentsTest extends TestCase
{
    /**
     * TC-N-01: 共通 submit ボタンは通常表示と送信中表示の両方を描画すること。
     */
    public function test_submit_button_component_renders_idle_and_loading_markup(): void
    {
        $html = $this->renderBlade(<<<'BLADE'
<x-ui.submit-button loading="保存中...">保存</x-ui.submit-button>
BLADE);

        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('x-bind:disabled="submitting"', $html);
        $this->assertStringContainsString('x-bind:aria-busy="submitting ? \'true\' : \'false\'"', $html);
        $this->assertStringContainsString('保存', $html);
        $this->assertStringContainsString('保存中...', $html);
    }

    /**
     * TC-B-01: loading 文言未指定時は既定文言を使うこと。
     */
    public function test_submit_button_component_uses_default_loading_label_when_omitted(): void
    {
        $html = $this->renderBlade(<<<'BLADE'
<x-ui.submit-button>送信</x-ui.submit-button>
BLADE);

        $this->assertStringContainsString('送信中...', $html);
    }

    /**
     * TC-R-01: loading 表示は初期非表示を維持しつつ Alpine が display class を切り替えられること。
     */
    public function test_submit_button_component_uses_toggleable_loading_display_classes(): void
    {
        $html = $this->renderBlade(<<<'BLADE'
<x-ui.submit-button loading="保存中...">保存</x-ui.submit-button>
BLADE);

        $this->assertStringContainsString('class="d-none"', $html);
        $this->assertStringContainsString(
            'x-bind:class="{ \'d-none\': !submitting, \'d-inline-flex align-items-center\': submitting }"',
            $html
        );
        $this->assertStringNotContainsString(
            'x-bind:class="submitting ? \'d-inline-flex align-items-center\' : \'d-none\'"',
            $html
        );
    }

    /**
     * TC-N-02: 対象フィールドの validation error を invalid-feedback として描画すること。
     */
    public function test_field_error_component_renders_invalid_feedback_for_target_field(): void
    {
        $html = $this->renderBlade(
            '<x-ui.field-error field="email" />',
            ['errors' => $this->makeViewErrorBag(['email' => 'メールアドレスは必須です。'])]
        );

        $this->assertStringContainsString('invalid-feedback', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('メールアドレスは必須です。', $html);
    }

    /**
     * TC-B-02: 対象フィールドに error がない場合は何も描画しないこと。
     */
    public function test_field_error_component_renders_nothing_when_field_has_no_error(): void
    {
        $html = $this->renderBlade(
            '<x-ui.field-error field="email" />',
            ['errors' => $this->makeViewErrorBag(['password' => 'パスワードは必須です。'])]
        );

        $this->assertSame('', trim($html));
    }

    /**
     * TC-N-03: summary error 一覧を Bootstrap alert として描画すること。
     */
    public function test_form_errors_component_renders_bootstrap_alert_list(): void
    {
        $html = $this->renderBlade(
            '<x-ui.form-errors />',
            [
                'errors' => $this->makeViewErrorBag([
                    'email' => 'メールアドレスは必須です。',
                    'password' => 'パスワードは必須です。',
                ]),
            ]
        );

        $this->assertStringContainsString('alert alert-danger', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('<li>メールアドレスは必須です。</li>', $html);
        $this->assertStringContainsString('<li>パスワードは必須です。</li>', $html);
    }

    /**
     * TC-N-04: Fortify 等が使う名前付きエラーバッグを form-errors で描画できること（例: updateProfileInformation）。
     */
    public function test_form_errors_component_renders_named_error_bag(): void
    {
        $html = $this->renderBlade(
            '<x-ui.form-errors bag="updateProfileInformation" />',
            [
                'errors' => (new ViewErrorBag)->put('updateProfileInformation', new MessageBag([
                    'email' => ['このメールアドレスは既に使用されています。'],
                ])),
            ]
        );

        $this->assertStringContainsString('このメールアドレスは既に使用されています。', $html);
        $this->assertStringContainsString('role="alert"', $html);
    }

    /**
     * TC-N-05: field-error が名前付きバッグのフィールドエラーを描画できること。
     */
    public function test_field_error_component_renders_named_error_bag(): void
    {
        $html = $this->renderBlade(
            '<x-ui.field-error field="email" bag="updateProfileInformation" />',
            [
                'errors' => (new ViewErrorBag)->put('updateProfileInformation', new MessageBag([
                    'email' => ['このメールアドレスは既に使用されています。'],
                ])),
            ]
        );

        $this->assertStringContainsString('invalid-feedback', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('このメールアドレスは既に使用されています。', $html);
    }

    /**
     * @param  array<string, string|list<string>>  $messages
     */
    private function makeViewErrorBag(array $messages): ViewErrorBag
    {
        $normalizedMessages = [];

        foreach ($messages as $field => $message) {
            $normalizedMessages[$field] = is_array($message) ? $message : [$message];
        }

        return (new ViewErrorBag)->put('default', new MessageBag($normalizedMessages));
    }

    /**
     * Render Blade inline and fail the test when the shared view contract is missing.
     *
     * @param  array<string, mixed>  $data
     */
    private function renderBlade(string $template, array $data = []): string
    {
        try {
            if (array_key_exists('errors', $data)) {
                view()->share('errors', $data['errors']);
            }

            return Blade::render($template, $data, true);
        } catch (\Throwable $throwable) {
            $this->fail($throwable->getMessage());
        }
    }
}
