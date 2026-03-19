<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppLayoutAlpineCspTest extends TestCase
{
    private string $renderedAppLayoutHtml;

    private string $renderedAdminLayoutHtml;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.asset_version' => '20260216_1']);
        $this->renderedAppLayoutHtml = $this->renderAppLayout();
        $this->renderedAdminLayoutHtml = $this->renderAdminLayout();
    }

    /**
     * App layout loads self-hosted Alpine.js CSP bundle.
     */
    public function test_layout_loads_self_hosted_alpine_csp_asset(): void
    {
        $this->assertScriptTagLoadsAsset(
            $this->renderedAppLayoutHtml,
            v_asset('assets/vendor/alpine/alpine.csp.min.js')
        );
    }

    /**
     * App layout no longer loads non-CSP Alpine bundle.
     */
    public function test_layout_does_not_load_non_csp_alpine_bundle(): void
    {
        $this->assertStringNotContainsString(
            'alpine.min.js',
            $this->renderedAppLayoutHtml
        );
    }

    /**
     * App layout loads app.js before Alpine CSP so Alpine.data() can register first.
     */
    public function test_app_layout_loads_application_script_before_alpine_csp_asset(): void
    {
        $this->assertAssetLoadsBefore(
            $this->renderedAppLayoutHtml,
            v_asset('assets/js/app.js'),
            v_asset('assets/vendor/alpine/alpine.csp.min.js')
        );
    }

    /**
     * Admin layout loads self-hosted Alpine.js CSP bundle.
     */
    public function test_admin_layout_loads_self_hosted_alpine_csp_asset(): void
    {
        $this->assertScriptTagLoadsAsset(
            $this->renderedAdminLayoutHtml,
            v_asset('assets/vendor/alpine/alpine.csp.min.js')
        );
    }

    /**
     * Admin layout no longer loads non-CSP Alpine bundle.
     */
    public function test_admin_layout_does_not_load_non_csp_alpine_bundle(): void
    {
        $this->assertStringNotContainsString(
            'alpine.min.js',
            $this->renderedAdminLayoutHtml
        );
    }

    /**
     * Admin layout loads app.js before Alpine CSP so Alpine.data() can register first.
     */
    public function test_admin_layout_loads_application_script_before_alpine_csp_asset(): void
    {
        $this->assertAssetLoadsBefore(
            $this->renderedAdminLayoutHtml,
            v_asset('assets/js/app.js'),
            v_asset('assets/vendor/alpine/alpine.csp.min.js')
        );
    }

    /**
     * Application script prepares the alpine:init hook for Alpine.data() registrations.
     */
    public function test_application_script_registers_alpine_data_on_alpine_init(): void
    {
        $script = File::get(public_path('assets/js/app.js'));

        $this->assertMatchesRegularExpression(
            '/document\.addEventListener\(\s*[\'"]alpine:init[\'"]\s*,/',
            $script
        );
    }

    /**
     * Application script registers the shared submitState Alpine data helper.
     */
    public function test_application_script_registers_submit_state_alpine_data(): void
    {
        $script = File::get(public_path('assets/js/app.js'));

        $this->assertMatchesRegularExpression(
            '/Alpine\.data\(\s*[\'"]submitState[\'"]\s*,/',
            $script
        );
        $this->assertStringContainsString('submitting: false', $script);
        $this->assertMatchesRegularExpression(
            '/startSubmitting\s*\(/',
            $script
        );
    }

    /**
     * Application script resets submitState when the page is restored from bfcache.
     */
    public function test_application_script_resets_submit_state_on_bfcache_restore(): void
    {
        $script = File::get(public_path('assets/js/app.js'));

        $this->assertMatchesRegularExpression(
            '/addEventListener\(\s*[\'"]pageshow[\'"]\s*,/',
            $script
        );
        $this->assertMatchesRegularExpression(
            '/event\.persisted/',
            $script
        );
        $this->assertMatchesRegularExpression(
            '/this\.submitting\s*=\s*false/',
            $script
        );
    }

    /**
     * Application script unregisters submitState pageshow listener when Alpine destroys the component.
     */
    public function test_application_script_unregisters_submit_state_pageshow_listener_on_destroy(): void
    {
        $script = File::get(public_path('assets/js/app.js'));

        $this->assertMatchesRegularExpression(
            '/pageShowHandler\s*:\s*null/',
            $script
        );
        $this->assertMatchesRegularExpression(
            '/destroy\s*\(\)\s*\{/',
            $script
        );
        $this->assertMatchesRegularExpression(
            '/removeEventListener\(\s*[\'"]pageshow[\'"]\s*,\s*this\.pageShowHandler\s*\)/',
            $script
        );
    }

    /**
     * Blade views do not inline Alpine object literals in x-data.
     */
    public function test_blade_views_do_not_inline_alpine_object_literals(): void
    {
        $inlineBladeViews = [];

        foreach (File::allFiles(resource_path('views')) as $viewFile) {
            if (! str_ends_with($viewFile->getFilename(), '.blade.php')) {
                continue;
            }

            if ($this->containsInlineAlpineObjectLiteral($viewFile->getContents())) {
                $inlineBladeViews[] = $viewFile->getRelativePathname();
            }
        }

        $this->assertSame([], $inlineBladeViews);
    }

    #[DataProvider('inlineAlpineObjectLiteralProvider')]
    public function test_inline_alpine_object_literal_detector(string $markup, bool $expected): void
    {
        $this->assertSame(
            $expected,
            $this->containsInlineAlpineObjectLiteral($markup)
        );
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function inlineAlpineObjectLiteralProvider(): array
    {
        return [
            'inline object literal' => [
                '<div x-data="{ open: false }"></div>',
                true,
            ],
            'minimal inline object literal' => [
                '<div x-data="{}"></div>',
                true,
            ],
            'single quoted inline object literal' => [
                "<div x-data='{ items: [] }'></div>",
                true,
            ],
            'wrapped inline object literal' => [
                '<div x-data="({ open: false })"></div>',
                true,
            ],
            'factory call with inline object literal' => [
                '<div x-data="dropdown({ open: false })"></div>',
                true,
            ],
            'unquoted inline object literal' => [
                '<div x-data={open:false}></div>',
                true,
            ],
            'empty double quoted x-data' => [
                '<div x-data=""></div>',
                false,
            ],
            'empty single quoted x-data' => [
                "<div x-data=''></div>",
                false,
            ],
            'blade comment containing inline object literal does not count' => [
                '{{-- <div x-data="{ foo: true }"></div> --}}',
                false,
            ],
            'blade echo x-data value does not count as inline object literal' => [
                '<div x-data="{{ $componentName }}"></div>',
                false,
            ],
            'blade raw echo x-data value does not count as inline object literal' => [
                '<div x-data="{!! $componentName !!}"></div>',
                false,
            ],
            'registered alpine data reference' => [
                '<div x-data="dropdown"></div>',
                false,
            ],
            'registered alpine data factory call' => [
                '<div x-data="dropdown()"></div>',
                false,
            ],
            'adjacent attribute object literal does not count as x-data inline object' => [
                '<div x-data="dropdown" data-config="{ open: false }"></div>',
                false,
            ],
            'adjacent attribute object literal does not count as x-data factory call' => [
                '<div x-data="dropdown()" data-config="{ open: false }"></div>',
                false,
            ],
        ];
    }

    private function renderAppLayout(): string
    {
        return Blade::render(<<<'BLADE'
@extends('layouts.app')

@section('content')
    <div>layout-check</div>
@endsection
BLADE);
    }

    private function renderAdminLayout(): string
    {
        return Blade::render(<<<'BLADE'
@extends('layouts.admin')

@section('page-title', 'layout-check')

@section('content')
    <div>layout-check</div>
@endsection
BLADE);
    }

    private function assertAssetLoadsBefore(string $html, string $firstAsset, string $secondAsset): void
    {
        $firstPosition = strpos($html, $firstAsset);
        $secondPosition = strpos($html, $secondAsset);

        $this->assertNotFalse($firstPosition);
        $this->assertNotFalse($secondPosition);
        $this->assertTrue(
            $firstPosition < $secondPosition,
            sprintf('Expected [%s] to load before [%s].', $firstAsset, $secondAsset)
        );
    }

    private function assertScriptTagLoadsAsset(string $html, string $asset): void
    {
        $this->assertMatchesRegularExpression(
            sprintf('/<script\b[^>]*\bsrc=(["\'])%s\1[^>]*><\/script>/s', preg_quote($asset, '/')),
            $html
        );
    }

    private function containsInlineAlpineObjectLiteral(string $contents): bool
    {
        foreach ($this->extractXDataAttributeValues($this->stripBladeComments($contents)) as $xDataValue) {
            if ($this->isPureBladeEchoValue($xDataValue)) {
                continue;
            }

            if (preg_match('/\{.*\}/s', $xDataValue) === 1) {
                return true;
            }
        }

        return false;
    }

    private function stripBladeComments(string $contents): string
    {
        return preg_replace('/\{\{--.*?--\}\}/s', '', $contents) ?? $contents;
    }

    private function isPureBladeEchoValue(string $xDataValue): bool
    {
        return preg_match('/^\s*(?:\{\{.*?\}\}|\{!!.*?!!\})\s*$/s', $xDataValue) === 1;
    }

    /**
     * @return list<string>
     */
    private function extractXDataAttributeValues(string $contents): array
    {
        preg_match_all(
            '/\bx-data\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/s',
            $contents,
            $matches,
            PREG_SET_ORDER
        );

        return array_values(array_map(
            static function (array $match): string {
                if (array_key_exists(3, $match)) {
                    return $match[3];
                }

                if (array_key_exists(2, $match)) {
                    return $match[2];
                }

                return $match[1] ?? '';
            },
            $matches
        ));
    }
}
