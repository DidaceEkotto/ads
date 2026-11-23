<?php

namespace App\Http\Requests\Publisher;

use App\Enums\AdZoneFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdZoneRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'format' => ['required', Rule::enum(AdZoneFormat::class)],
            'is_native' => ['boolean'],
        ];
    }
}
