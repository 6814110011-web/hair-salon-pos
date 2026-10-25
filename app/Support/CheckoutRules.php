<?php

namespace App\Support;

final class CheckoutRules
{
    public static function rules(): array
    {
        return ['checkout_token' => 'required|uuid', 'customer_id' => 'nullable|integer|min:1', 'promotion_id' => 'nullable|integer|min:1',
            'items' => 'required|array|min:1|max:100', 'items.*.type' => 'required|in:product,service', 'items.*.id' => 'required|integer|min:1',
            'items.*.qty' => 'required|integer|min:1|max:999', 'items.*.expected_price' => 'required|numeric|min:0|max:99999999.99|decimal:0,2',
            'items.*.stylist_id' => 'nullable|integer|min:1', 'method' => 'required|in:cash,promptpay,transfer,card',
            'received' => 'required_if:method,cash|nullable|numeric|min:0|max:99999999.99|decimal:0,2',
            'ref_no' => 'required_unless:method,cash|nullable|string|max:255'];
    }

    public static function messages(): array
    {
        return ['items.required' => 'กรุณาเพิ่มสินค้า หรือบริการอย่างน้อยหนึ่งรายการ', 'items.*.expected_price.required' => 'กรุณาตรวจสอบราคาของรายการขาย', 'items.*.qty.max' => 'จำนวนต่อรายการต้องไม่เกิน 999', 'ref_no.required_unless' => 'กรุณาระบุเลขอ้างอิงการชำระเงิน', 'received.required_if' => 'กรุณากรอกเงินที่รับ', 'required' => 'กรุณากรอกข้อมูลให้ครบ', 'integer' => 'จำนวนต้องเป็นจำนวนเต็ม', 'decimal' => 'จำนวนเงินต้องมีทศนิยมไม่เกิน 2 ตำแหน่ง', 'min' => 'ข้อมูลจำนวนหรือยอดเงินไม่ถูกต้อง', 'max' => 'ข้อมูลเกินขอบเขตที่กำหนด', 'in' => 'ตัวเลือกไม่ถูกต้อง'];
    }
}
