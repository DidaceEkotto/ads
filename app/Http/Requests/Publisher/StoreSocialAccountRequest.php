<?php

namespace App\Http\Requests\Publisher;

use App\Enums\SocialPlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSocialAccountRequest extends FormRequest
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
            'platform' => ['required', Rule::enum(SocialPlatform::class)],
            'handle' => [
                'required',
                'string',
                'max:150',
                Rule::unique('social_accounts', 'handle')->where(
                    fn ($q) => $q->where('platform', $this->input('platform'))
                ),
            ],
            'profile_url' => ['required', 'url:http,https', 'max:512'],
        ];
    }
}
