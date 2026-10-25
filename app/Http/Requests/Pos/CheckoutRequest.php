<?php

namespace App\Http\Requests\Pos;

use App\Support\CheckoutRules;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin', 'cashier') ?? false;
    }

    public function rules(): array
    {
        return CheckoutRules::rules();
    }

    public function messages(): array
    {
        return CheckoutRules::messages();
    }
}
