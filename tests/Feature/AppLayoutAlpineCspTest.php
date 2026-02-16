<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AppLayoutAlpineCspTest extends TestCase
{
    /**
     * App layout loads self-hosted Alpine.js CSP bundle.
     */
    public function test_layout_loads_self_hosted_alpine_csp_asset(): void
    {
        // Given: app layout is rendered via Blade with a fixed asset version
        config(['app.asset_version' => '20260216_1']);

        // When: a view extending layouts.app is rendered
        $html = Blade::render(<<<'BLADE'
@extends('layouts.app')

@section('content')
    <div>layout-check</div>
@endsection
BLADE);

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
        // Given: app layout is rendered via Blade with a fixed asset version
        config(['app.asset_version' => '20260216_1']);

        // When: a view extending layouts.app is rendered
        $html = Blade::render(<<<'BLADE'
@extends('layouts.app')

@section('content')
    <div>layout-check</div>
@endsection
BLADE);

        // Then: old non-CSP Alpine bundle URL is not included
        $this->assertStringNotContainsString(
            v_asset('assets/vendor/alpine/alpine.min.js'),
            $html
        );
    }
}
