<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',          // **假別名稱**（例如：特休假、病假）
        'code',          // **假別代碼**（例如：annual、sick）
        'default_hours', // **預設請假時數**（例如：病假 30 天）
        'gender_limit'   // **性別限制**（`male` / `female`，null 表示不限制）
    ];

    /**
     * **關聯：一種請假類型可以對應多個員工的請假餘額**
     */
    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * **關聯：一種請假類型可以對應多個請假申請**
     */
    public function leaves()
    {
        return $this->hasMany(Leave::class, 'leave_type_id');
    }
}
