<?php

namespace App\Http\Requests\Advertiser;

use App\Enums\AdZoneFormat;
use App\Enums\BillingModel;
use App\Enums\CampaignType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
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
            array_map(fn (AdZoneFormat $f) => $f->value, AdZoneFormat::cases()),
            ['social']
        );

        return [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(CampaignType::class)],
            'billing_model' => ['required', Rule::enum(BillingModel::class)],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'starts_at' => ['nullable', 'date'],

            // Créative principale (Variante A) + Variante B optionnelle (Test A/B)
            'title' => ['required', 'string', 'max:120'],
            'body_text' => ['nullable', 'string', 'max:1000'],
            'destination_url' => ['required', 'url:http,https', 'max:512'],
            'format' => ['required', Rule::in($formats)],
            // 1 Mo max au niveau FormRequest ; CreativeUploadService applique 150 Ko (img), 500 Ko (GIF), 1 Mo (vidéo)
            'media' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm', 'max:1024'],
            'title_b' => ['nullable', 'string', 'max:120'],
            'media_b' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm', 'max:1024'],

            // Ciblage (au moins une ville camerounaise obligatoire)
            'cities' => ['required', 'array', 'min:1'],
            'cities.*' => ['string', 'max:60'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', Rule::in(['fr', 'en'])],
            'devices' => ['nullable', 'array'],
            'devices.*' => ['string', Rule::in(['mobile', 'desktop', 'tablet'])],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cities.required' => __('Veuillez sélectionner au moins une ville de diffusion.'),
            'cities.min' => __('Veuillez sélectionner au moins une ville de diffusion.'),
        ];
    }
}
