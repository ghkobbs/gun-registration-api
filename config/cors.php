<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',    // React dev server
        'http://localhost:8080',    // Vue dev server
        'http://localhost:4200',    // Angular dev server
        'https://yourdomain.com',   // Production domain
        // Add your frontend domains
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];