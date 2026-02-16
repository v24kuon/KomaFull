<?php

namespace Tests\Feature;

use Tests\TestCase;

class ContentSecurityPolicyReportOnlyTest extends TestCase
{
    /**
     * Web responses include CSP Report-Only header.
     */
    public function test_report_only_header_is_attached_to_web_response(): void
    {
        // Given: the root page is requested
        // When: a web response is returned
        $response = $this->get('/');

        // Then: CSP Report-Only header is attached
        $response->assertOk();
        $response->assertHeader('Content-Security-Policy-Report-Only');
    }

    /**
     * Report-Only policy remains non-enforcing and keeps unsafe directives disabled.
     */
    public function test_report_only_policy_is_non_enforcing_and_disallows_unsafe_directives(): void
    {
        // Given: the root page is requested
        // When: a web response is returned
        $response = $this->get('/');

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
        // Given: report-only mode is disabled
        config(['security.csp.report_only_enabled' => false]);

        // When: a web response is returned
        $response = $this->get('/');

        // Then: report-only header is not attached
        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    /**
     * Report-Only header is skipped when policy is empty.
     */
    public function test_report_only_header_is_not_attached_when_policy_is_empty(): void
    {
        // Given: report-only policy is empty
        config(['security.csp.report_only_policy' => '']);

        // When: a web response is returned
        $response = $this->get('/');

        // Then: report-only header is not attached
        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }
}
