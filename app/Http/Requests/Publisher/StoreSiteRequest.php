<?php

namespace App\Http\Requests\Publisher;

use App\Enums\AdZoneFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPublisher() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('domain')) {
            $raw = strtolower(trim((string) $this->input('domain')));
            $host = parse_url(str_contains($raw, '://') ? $raw : "https://{$raw}", PHP_URL_HOST) ?? $raw;
            $this->merge([
                'domain' => preg_replace('/^www\./', '', (string) $host),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'domain' => [
                'required',
                'string',
                'max:190',
                'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/',
                'unique:sites,domain',
            ],
            'category' => ['required', 'string', 'max:50'],
            'language' => ['required', Rule::in(['fr', 'en'])],
            'allowed_formats' => ['nullable', 'array', 'min:1'],
            'allowed_formats.*' => [Rule::enum(AdZoneFormat::class)],
        ];
    }
}
