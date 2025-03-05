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
            'leave_type' => ['required', Rule::in(Leave::LEAVE_TYPE)],  // 必填，必須是合法假別
            'reason' => ['required', 'string'],  // 必填，必須是文字
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
    }
}
