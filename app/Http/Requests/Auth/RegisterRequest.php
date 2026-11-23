<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^\+?237[0-9]{9}$/', 'unique:users,phone'],
            'role' => ['required', Rule::in([UserRole::Advertiser->value, UserRole::Publisher->value])],
            'city' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
                'regex:/[@_-]/',
            ],
            'referral_code' => ['nullable', 'string', 'max:16'],
            'accept_terms' => ['accepted'],
        ];
    }

    /**
     * Messages d'erreur personnalisés en français.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('Le numéro doit être un numéro camerounais valide (ex: +2376XXXXXXXX).'),
            'password.regex' => __('Le mot de passe doit contenir au moins un caractère spécial parmi @, _ ou -.'),
            'accept_terms.accepted' => __('Vous devez accepter les conditions d\'utilisation et la politique de confidentialité.'),
        ];
    }
}
