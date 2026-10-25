<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin', 'cashier') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['nullable', 'string', 'max:255'], 'phone' => ['required', 'string', 'max:15', Rule::unique('customers')->ignore($this->route('customer'))]];
    }

    public function messages(): array
    {
        return ['phone.required' => 'กรุณากรอกเบอร์โทร', 'phone.unique' => 'เบอร์โทรนี้มีอยู่ในระบบแล้ว', 'phone.max' => 'เบอร์โทรยาวได้ไม่เกิน 15 ตัวอักษร', 'name.max' => 'ชื่อยาวได้ไม่เกิน 255 ตัวอักษร'];
    }
}
