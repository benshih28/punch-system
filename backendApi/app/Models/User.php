<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject; //假如Laravel版本太新
use Spatie\Permission\Traits\HasRoles; // ✅ 引入 Spatie 的 HasRoles Trait
class User extends Authenticatable implements JWTSubject  // 實作JWT
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasRoles, HasFactory, Notifiable; // ✅ 使用 Spatie 提供的 HasRoles Trait

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'gender', // 新增 gender 欄位
    ];


    // 這裡是將 email 轉為小寫
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // JWT 必須實作的方法
    // getKey() 預設就是 id，所以這裡直接回傳 id
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    // 自訂 JWT Payload 內容
    public function getJWTCustomClaims()
    {
        return [];
    }


    // ✅【Spatie 已經提供 `hasRole()` 方法，不需要再手動寫】

    /**
     * 檢查使用者是否擁有某個權限
     */
    public function hasPermission($permission)
    {
        return $this->hasPermissionTo($permission);
    }

    
    // 使用者擔任主管的部門 (1對1)
    public function managedDepartment()
    {
        return $this->hasOne(Department::class, 'manager_id', 'id');
    }
}
