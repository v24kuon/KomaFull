<?php

namespace Tests\Feature;

use Tests\TestCase;

class VAssetHelperTest extends TestCase
{
    /**
     * v_asset helper adds configured version query parameter.
     */
    public function test_v_asset_helper_appends_asset_version_query(): void
    {
        // Given: asset version is configured
        config(['app.asset_version' => '20260211_1']);

        // When: the helper generates an asset URL
        $url = v_asset('assets/css/app.css');

        // Then: the URL includes the configured version query
        $this->assertSame(asset('assets/css/app.css').'?v=20260211_1', $url);
    }

    /**
     * v_asset helper rejects null path input.
     */
    public function test_v_asset_helper_throws_type_error_for_null_path(): void
    {
        // Given: an invalid null path input
        $helper = new \ReflectionFunction('v_asset');

        // When: the helper is called with null path
        // Then: a TypeError is thrown by the function signature
        $this->expectException(\TypeError::class);
        $helper->invoke(null);
    }
}
