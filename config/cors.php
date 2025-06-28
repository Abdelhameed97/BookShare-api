<?php

// this is the configuration for CORS (Cross-Origin Resource Sharing)
// it allows the frontend to make requests to the backend API
// the frontend is running on http://localhost:3000
return[
    'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie', 'user', 'forgot-password', 'reset-password'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];