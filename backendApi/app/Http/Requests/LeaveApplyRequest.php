<?php

namespace App\Http\Requests;

use App\Models\Leave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveApplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 這裡直接return true，因為是登入後的API，已經有auth保護了
    }

    // 申請請假格式驗證
    // 須注意「start_time」要填入「20XX-XX-XX XX:XX」
    public function rules(): array
    {
        return [
            'start_time' => ['required', 'date'],  // 必填，格式必須是日期時間
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],  // 必填，要晚於或等於start_time
            'leave_type' => ['required', 'exists:leave_types,id'],  // 驗證 leave_type 是 leave_types 表中的有效 id
            'reason' => ['required', 'string'],  // 必填，必須是文字
            'attachment' => 'nullable|file|max:10240',  // 驗證 attachment 是 files 表中的有效 id
        ];
    }
}
