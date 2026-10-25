<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class CancelSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['reason' => 'required|string|max:200'];
    }

    public function messages(): array
    {
        return ['reason.required' => 'กรุณาระบุเหตุผลยกเลิกบิล', 'reason.max' => 'เหตุผลยาวได้ไม่เกิน 200 ตัวอักษร'];
    }
}
