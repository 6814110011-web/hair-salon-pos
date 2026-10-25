<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['from' => $this->input('from') ?: now()->toDateString(), 'to' => $this->input('to') ?: now()->toDateString()]);
    }

    public function rules(): array
    {
        return ['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from'];
    }

    public function messages(): array
    {
        return ['date_format' => 'กรุณาเลือกวันที่ให้ถูกต้อง', 'to.after_or_equal' => 'วันสิ้นสุดต้องไม่ก่อนวันเริ่มต้น'];
    }
}
