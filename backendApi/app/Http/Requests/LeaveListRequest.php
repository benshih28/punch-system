<?php

namespace App\Http\Requests;

use App\Models\Leave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    // 查詢請假紀錄格式驗證
    // 查詢時要填的「start_date」格式是 20XX-XX-XX
    public function rules(): array
    {
        return [
            //
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'leave_type' => ['required', 'exists:leave_types,id'],  // 驗證 leave_type 是 leave_types 表中的有效 id
            'attachment' => 'nullable|exists:files,id',  // 驗證 attachment 是 files 表中的有效 id
            'status' => 'nullable|in:pending,approved,rejected',
        ];
    }
}
