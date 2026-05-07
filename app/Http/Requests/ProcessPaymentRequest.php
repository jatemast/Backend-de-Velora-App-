<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => 'required|in:credit_card,debit_card,cash,transfer,paypal,stripe,mercadopago,other',
            'currency' => 'nullable|string|size:3',
            'details' => 'nullable|array',
        ];
    }
}
