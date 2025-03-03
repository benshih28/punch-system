<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'], // 允許 API 路徑
    'allowed_methods' => ['*'], // 允許所有請求方法
    'allowed_origins' => ['http://localhost:5173'], // 前端網址
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Authorization', 'Content-Type', 'X-Requested-With'],
    'exposed_headers' => ['Authorization'], // 確保前端能讀取 Authorization 標頭
    'max_age' => 0,
    'supports_credentials' => true, // **允許攜帶憑證（重要！）**
];

