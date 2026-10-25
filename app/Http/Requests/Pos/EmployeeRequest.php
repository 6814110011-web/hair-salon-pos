<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'lowercase', 'max:255', Rule::unique('users')->ignore($this->route('employee'))],
            'phone' => 'nullable|string|max:15', 'role' => ['required', Rule::in(['admin', 'cashier', 'stylist'])],
            'commission_rate' => 'required|numeric|min:0|max:100|decimal:0,2', 'is_active' => 'required|boolean',
            'password' => [$this->route('employee') ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return ['required' => 'กรุณากรอก :attribute', 'email.email' => 'อีเมลไม่ถูกต้อง', 'email.unique' => 'อีเมลนี้มีอยู่แล้ว', 'password.min' => 'รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร', 'password.confirmed' => 'รหัสผ่านยืนยันไม่ตรงกัน', 'commission_rate.max' => 'คอมมิชชันต้องอยู่ระหว่าง 0–100', 'commission_rate.min' => 'คอมมิชชันต้องอยู่ระหว่าง 0–100'];
    }

    public function attributes(): array
    {
        return ['name' => 'ชื่อ', 'email' => 'อีเมล', 'role' => 'บทบาท', 'commission_rate' => 'อัตราคอมมิชชัน', 'password' => 'รหัสผ่าน'];
    }
}
