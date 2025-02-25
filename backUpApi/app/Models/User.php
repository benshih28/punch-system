<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject; // ← 使用新的 JWTSubject 契約 // 引入 JWTSubject

class User extends Authenticatable implements JWTSubject  // 實作JWT
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
}

