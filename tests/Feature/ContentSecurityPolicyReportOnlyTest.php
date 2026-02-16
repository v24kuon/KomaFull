<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ContentSecurityPolicyReportOnlyTest extends TestCase
{
    /**
     * Web responses include CSP Report-Only header.
     */
    public function test_report_only_header_is_attached_to_web_response(): void
    {
        // Given: a test route is available with web middleware
        Route::middleware(['web'])->get('/csp-test', fn () => response('ok'));

        // When: a web response is returned
        $response = $this->get('/csp-test');

        // Then: CSP Report-Only header is attached
        $response->assertOk();
        $response->assertHeader('Content-Security-Policy-Report-Only');
    }

    /**
     * Report-Only policy remains non-enforcing and keeps unsafe directives disabled.
     */
    public function test_report_only_policy_is_non_enforcing_and_disallows_unsafe_directives(): void
    {
        // Given: a test route is available with web middleware
        Route::middleware(['web'])->get('/csp-test', fn () => response('ok'));

        // When: a web response is returned
        $response = $this->get('/csp-test');

        // Then: enforcing CSP header is not attached
        $response->assertHeaderMissing('Content-Security-Policy');

        // Then: Report-Only policy contains expected directives
        $policy = (string) $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("script-src 'self'", $policy);
        $this->assertStringNotContainsString("'unsafe-inline'", $policy);
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
    }

    /**
     * Report-Only header is skipped when feature flag is disabled.
     */
    public function test_report_only_header_is_not_attached_when_disabled(): void
    {
        // Given: report-only mode is disabled and a test route is available with web middleware
        Config::set('security.csp.report_only_enabled', false);
        Route::middleware(['web'])->get('/csp-test', fn () => response('ok'));

        // When: a web response is returned
        $response = $this->get('/csp-test');

        // Then: report-only header is not attached
        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    /**
     * Report-Only header is skipped when policy is empty.
     */
    public function test_report_only_header_is_not_attached_when_policy_is_empty(): void
    {
        // Given: report-only policy is empty and a test route is available with web middleware
        Config::set('security.csp.report_only_policy', '');
        Route::middleware(['web'])->get('/csp-test', fn () => response('ok'));

        // When: a web response is returned
        $response = $this->get('/csp-test');

        // Then: report-only header is not attached
        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    /**
     * Report-Only header is skipped when policy contains only whitespace.
     */
    public function test_report_only_header_is_not_attached_when_policy_is_whitespace_only(): void
    {
        // Given: report-only policy is whitespace only and a test route is available with web middleware
        Config::set('security.csp.report_only_policy', '   ');
        Route::middleware(['web'])->get('/csp-test', fn () => response('ok'));

        // When: a web response is returned
        $response = $this->get('/csp-test');

        // Then: report-only header is not attached
        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }
}
