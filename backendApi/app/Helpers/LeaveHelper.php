<?php

namespace App\Helpers;

class LeaveHelper
{
    // 取得所有假別清單
    public static function allLeaveTypes(): array
    {
        return [
            ['key' => 'personal', 'label' => self::typeLabel('personal')],
            ['key' => 'sick', 'label' => self::typeLabel('sick')],
            ['key' => 'official', 'label' => self::typeLabel('official')],
            ['key' => 'marriage', 'label' => self::typeLabel('marriage')],
            ['key' => 'maternity', 'label' => self::typeLabel('maternity')],
            ['key' => 'funeral', 'label' => self::typeLabel('funeral')],
            ['key' => 'annual', 'label' => self::typeLabel('annual')],
            ['key' => 'menstrual', 'label' => self::typeLabel('menstrual')],
        ];
    }

    // 取得所有狀態清單
    public static function allLeaveStatuses(): array
    {
        return [
            ['key' => 'pending', 'label' => self::statusLabel('pending')],
            ['key' => 'approved', 'label' => self::statusLabel('approved')],
            ['key' => 'rejected', 'label' => self::statusLabel('rejected')],
        ];
    }
    
    // 假別對應中文
    public static function typeLabel($type)
    {
        return match ($type) {
            'personal' => '事假',
            'sick' => '病假',
            'official' => '公假',
            'marriage' => '婚假',
            'maternity' => '產假',
            'funeral' => '喪假',
            'annual' => '特休假',
            'menstrual' => '生理假',
            default => '未知假別',
        };
    }


    // 狀態對應中文
    public static function statusLabel($status)
    {
        return match ($status) {
            'pending' => '待審核',
            'approved' => '已審核',
            'rejected' => '已退回',
            default => '未知狀態',
        };
    }
}
