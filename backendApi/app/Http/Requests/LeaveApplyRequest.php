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

    public function rules(): array
    {
        return [
            'start_time' => ['required', 'date'],  // 必填，格式必須是日期時間
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],  // 必填，要晚於或等於start_time
            'leave_type' => ['required', Rule::in(Leave::TYPES)],  // 必填，必須是合法假別
            'reason' => ['required', 'string'],  // 必填，必須是文字
        ];
    }

    // public function messages(): array
    // {
    //     return [
    //         'start_time.required' => '請假開始時間為必填',
    //         'start_time.date' => '開始時間格式錯誤',
    //         'end_time.required' => '請假結束時間為必填',
    //         'end_time.date' => '結束時間格式錯誤',
    //         'end_time.after_or_equal' => '結束時間必須晚於或等於開始時間',
    //         'leave_type.required' => '請假類型為必填',
    //         'leave_type.in' => '假別類型錯誤，僅允許：事假、病假、公假…',
    //         'reason.required' => '請假原因為必填',
    //         'reason.string' => '請假原因必須是文字',
    //     ];
    // }
}
