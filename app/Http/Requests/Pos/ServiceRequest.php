<?php

namespace App\Http\Requests\Pos;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:255', 'category' => ['required', Rule::in(array_keys(Service::CATEGORIES))], 'price' => 'required|numeric|min:0|max:99999999.99|decimal:0,2', 'duration_minutes' => 'required|integer|min:1|max:2147483647', 'is_active' => 'required|boolean'];
    }

    public function messages(): array
    {
        return ['required' => 'กรุณากรอก :attribute', 'category.in' => 'ประเภทบริการไม่ถูกต้อง', 'price.min' => 'ราคาต้องไม่ติดลบ', 'price.max' => 'ราคาเกินขอบเขต', 'duration_minutes.min' => 'ระยะเวลาต้องมากกว่า 0 นาที', 'integer' => ':attribute ต้องเป็นจำนวนเต็ม'];
    }

    public function attributes(): array
    {
        return ['name' => 'ชื่อบริการ', 'price' => 'ราคา', 'duration_minutes' => 'ระยะเวลา'];
    }
}
