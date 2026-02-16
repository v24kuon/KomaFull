<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AppLayoutAlpineCspTest extends TestCase
{
    private string $renderedLayoutHtml;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.asset_version' => '20260216_1']);
        $this->renderedLayoutHtml = $this->renderLayout();
    }

    /**
     * App layout loads self-hosted Alpine.js CSP bundle.
     */
    public function test_layout_loads_self_hosted_alpine_csp_asset(): void
    {
        // Given: app layout HTML is pre-rendered in setUp with fixed asset version
        // When: script tags are inspected from the rendered HTML
        $html = $this->renderedLayoutHtml;

        // Then: Alpine CSP bundle URL is included in the script tag
        $this->assertStringContainsString(
            '<script defer src="'.v_asset('assets/vendor/alpine/alpine.csp.min.js').'"></script>',
            $html
        );
    }

    /**
     * App layout no longer loads non-CSP Alpine bundle.
     */
    public function test_layout_does_not_load_non_csp_alpine_bundle(): void
    {
        // Given: app layout HTML is pre-rendered in setUp with fixed asset version
        // When: script tags are inspected from the rendered HTML
        $html = $this->renderedLayoutHtml;

        // Then: old non-CSP Alpine bundle filename is not included in any form
        $this->assertStringNotContainsString(
            'alpine.min.js',
            $html
        );
    }

    private function renderLayout(): string
    {
        return Blade::render(<<<'BLADE'
@extends('layouts.app')

@section('content')
    <div>layout-check</div>
@endsection
BLADE);
    }
}
