<?php

namespace App\Http\Requests\Billing;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdvertiser() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $minDeposit = (int) Setting::getValue('min_deposit_xaf', 2000);

        return [
            'amount_xaf' => ['required', 'integer', "min:{$minDeposit}", 'max:10000000'],
            'idempotency_key' => ['required', 'string', 'uuid'],
        ];
    }
}
