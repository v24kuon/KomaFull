<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddContentSecurityPolicyReportOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('security.csp.report_only_enabled', true)) {
            return $response;
        }

        $policy = (string) config(
            'security.csp.report_only_policy',
            "default-src 'self'; script-src 'self'; style-src 'self';"
        );

        if (trim($policy) !== '') {
            $response->headers->set('Content-Security-Policy-Report-Only', $policy);
        }

        return $response;
    }
}
