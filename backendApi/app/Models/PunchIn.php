<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PunchIn extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'timestamp'];
    
    protected $casts = [
        'timestamp' => 'datetime', // ✅ 轉換為 Carbon 實例
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

