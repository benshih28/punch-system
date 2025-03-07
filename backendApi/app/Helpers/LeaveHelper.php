<?php

namespace App\Helpers;

class LeaveHelper
{

    // 取得所有狀態清單
    public static function allLeaveStatuses(): array
    {
        return [
            ['key' => 'pending', 'label' => self::statusLabel('pending')],
            ['key' => 'approved', 'label' => self::statusLabel('approved')],
            ['key' => 'rejected', 'label' => self::statusLabel('rejected')],
        ];
    }

    // 狀態對應中文
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => '審核中',
            'approved' => '已通過',
            'rejected' => '已退回',
        };
    }
}
