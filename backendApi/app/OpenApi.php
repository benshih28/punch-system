<?php

namespace App;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Punch System API",
 *     description="打卡系統 API 文件",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="使用 JWT token進行認證，格式為 'Bearer {token}'"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1, description="使用者 ID"),
 *     @OA\Property(property="name", type="string", example="John Doe", description="使用者名稱"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="電子郵件")
 * )
 */
class OpenApi
{
    // 這個類可以是空的，只是用來存放全局註解
}