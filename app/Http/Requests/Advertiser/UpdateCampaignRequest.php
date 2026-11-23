<?php

namespace App\Http\Requests\Advertiser;

use App\Enums\AdZoneFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
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
        $formats = array_merge(
            array_column(AdZoneFormat::cases(), 'value'),
            ['social']
        );

        return [
            'name' => ['required', 'string', 'max:120'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'title' => ['required', 'string', 'max:120'],
            'body_text' => ['nullable', 'string', 'max:500'],
            'destination_url' => ['required', 'url', 'max:255'],
            'format' => ['required', Rule::in($formats)],
            'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm', 'max:1024'],
            'cities' => ['required', 'array', 'min:1'],
            'cities.*' => ['string', 'max:60'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', 'max:10'],
            'devices' => ['nullable', 'array'],
            'devices.*' => ['string', 'max:20'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:40'],
        ];
    }
}
