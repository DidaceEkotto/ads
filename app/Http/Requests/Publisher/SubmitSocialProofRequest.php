<?php

namespace App\Http\Requests\Publisher;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSocialProofRequest extends FormRequest
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
            'post_url' => ['required', 'url:http,https', 'max:512'],
            'has_disclosure_tag' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'has_disclosure_tag.accepted' => __('Vous devez inclure la mention obligatoire #pub ou #partenariat dans votre publication.'),
        ];
    }
}
