<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveTypeUpdateRequest extends FormRequest
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
    // 更新假別格式驗證
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:leave_types,name,' . $this->route('id'),
            'description' => 'required|string|max:255',
            'total_hours' => 'nullable|integer|min:0',
        ];
    }
}
