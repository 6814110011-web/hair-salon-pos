<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class StockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['mode' => 'required|in:receive,adjust', 'quantity' => 'required_if:mode,receive|nullable|integer|min:1|max:2147483647', 'target_quantity' => 'required_if:mode,adjust|nullable|integer|min:0|max:2147483647', 'reason' => 'required|string|max:255'];
    }

    public function messages(): array
    {
        return ['reason.required' => 'กรุณาระบุเหตุผล', 'required_if' => 'กรุณากรอกจำนวน', 'min' => 'จำนวนไม่ถูกต้อง', 'max' => 'ข้อมูลเกินขอบเขต', 'integer' => 'จำนวนต้องเป็นจำนวนเต็ม'];
    }
}
