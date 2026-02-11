<?php

if (! function_exists('v_asset')) {
    /**
     * Generate an asset path for the application with cache busting version.
     */
    function v_asset(string $path, ?bool $secure = null): string
    {
        $version = env('ASSET_VERSION', date('Ymd_1'));

        return asset($path, $secure).'?v='.$version;
    }
}
