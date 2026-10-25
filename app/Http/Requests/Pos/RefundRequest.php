<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['ref_no' => 'required|string|max:255'];
    }

    public function messages(): array
    {
        return ['ref_no.required' => 'กรุณาระบุเลขอ้างอิงหรือหลักฐานการคืนเงินจริง', 'ref_no.max' => 'หลักฐานยาวได้ไม่เกิน 255 ตัวอักษร'];
    }
}
