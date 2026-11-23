<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPublisher() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::in(['mtn_momo', 'orange_money'])],
            'account_number' => ['required', 'string', 'regex:/^\+?237[0-9]{9}$/'],
            'account_name' => ['required', 'string', 'max:120'],
            'is_default' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'account_number.regex' => __('Le numéro Mobile Money doit être un numéro camerounais valide (+2376XXXXXXXX).'),
        ];
    }
}
