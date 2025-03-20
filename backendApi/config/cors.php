<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'], // 只開放 API 路由 ，sanctum看未來要不要開放

    'allowed_methods' => ['*'], // 允許所有 HTTP 方法 (GET, POST, PUT, DELETE, OPTIONS)

    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')], // 允許特定前端網址

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Authorization', 'Content-Type', 'X-Requested-With'], // 限制必要的 Headers (可避免安全問題)

    'exposed_headers' => ['Authorization'], // 確保前端可以讀取 `Authorization` 標頭

    'max_age' => 0,

    'supports_credentials' => false,  // 設為 `false`，因為 JWT 不需要 `cookies`

];