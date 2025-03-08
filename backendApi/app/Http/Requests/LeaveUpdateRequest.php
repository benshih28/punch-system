<?php

namespace App\Http\Requests;

use App\Models\Leave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_time' => ['required', 'date'],  // 必填，格式必須是日期時間
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],  // 必填，要晚於或等於start_time
            'leave_type' => ['required', 'integer', 'exists:leave_types,id'],  // 驗證 leave_type 是 leave_types 表中的有效 id
            'reason' => ['required', 'string'],  // 必填，必須是文字
            'attachment' => 'nullable|file|max:10240', // 允許 10MB 內的文件
        ];
    }
}
