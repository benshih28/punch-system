<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveTypeCreateRequest extends FormRequest
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

    // 新增假別驗證
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:leave_types,name', // 假別名稱唯一
            'description' => 'required|string|max:255', // 假別中文
            'total_hours' => 'nullable|integer|min:0',  //不是必填，並且驗證它是數字，且不小於 0
        ];
    }
}
