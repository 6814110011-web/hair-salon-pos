<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['sku' => ['required', 'string', 'max:30', Rule::unique('products')->ignore($this->route('product'))],
            'name' => 'required|string|max:255', 'cost_price' => 'required|numeric|min:0|max:99999999.99|decimal:0,2',
            'sell_price' => 'required|numeric|min:0|max:99999999.99|decimal:0,2', 'min_stock' => 'required|integer|min:0|max:2147483647',
            'unit' => 'required|string|max:20', 'is_active' => 'required|boolean'];
    }

    public function messages(): array
    {
        return ['required' => 'กรุณากรอก :attribute', 'sku.unique' => 'รหัสสินค้านี้มีอยู่แล้ว', 'min' => ':attribute ต้องไม่ติดลบ', 'decimal' => ':attribute ต้องมีทศนิยมไม่เกิน 2 ตำแหน่ง', 'max' => ':attribute เกินขอบเขตที่กำหนด'];
    }

    public function attributes(): array
    {
        return ['sku' => 'รหัสสินค้า', 'name' => 'ชื่อสินค้า', 'cost_price' => 'ต้นทุน', 'sell_price' => 'ราคาขาย', 'min_stock' => 'จุดเตือนสต็อก', 'unit' => 'หน่วย'];
    }
}
