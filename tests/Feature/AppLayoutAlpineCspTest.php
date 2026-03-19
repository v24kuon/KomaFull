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
        $this->assertStringContainsString(
            '<script defer src="'.v_asset('assets/vendor/alpine/alpine.csp.min.js').'"></script>',
            $this->renderedAppLayoutHtml
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
        $this->assertStringContainsString(
            '<script defer src="'.v_asset('assets/vendor/alpine/alpine.csp.min.js').'"></script>',
            $this->renderedAdminLayoutHtml
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

        $this->assertStringContainsString(
            "document.addEventListener('alpine:init'",
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

    private function containsInlineAlpineObjectLiteral(string $contents): bool
    {
        foreach ($this->extractXDataAttributeValues($contents) as $xDataValue) {
            if (preg_match('/\{.*\}/s', $xDataValue) === 1) {
                return true;
            }
        }

        return false;
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
                return $match[1] !== ''
                    ? $match[1]
                    : ($match[2] !== '' ? $match[2] : ($match[3] ?? ''));
            },
            $matches
        ));
    }
}
