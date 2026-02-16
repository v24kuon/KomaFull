<?php

return [
    'csp' => [
        'report_only_enabled' => env('CSP_REPORT_ONLY_ENABLED', true),
        'report_only_policy' => implode(' ', [
            "default-src 'self';",
            "base-uri 'self';",
            "form-action 'self';",
            "frame-ancestors 'self';",
            "script-src 'self';",
            "style-src 'self';",
            "img-src 'self' data:;",
            "font-src 'self' data:;",
            "connect-src 'self';",
        ]),
    ],
];
