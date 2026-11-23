<?php

namespace App\Http\Controllers\Advertiser;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProfileController extends Controller
{
    public function edit(Request $request): InertiaResponse
    {
        $user = $request->user();

        return Inertia::render('Advertiser/Profile', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatarUrl(),
                'initials' => $user->initials(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'regex:/^\+?237[0-9]{9}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:300'],
            'remove_avatar' => ['boolean'],
        ]);

        if ($request->boolean('remove_avatar') && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $validated['avatar_path'] = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $file = $request->file('avatar');
            $ext = $file->guessExtension() ?: 'png';
            $validated['avatar_path'] = Storage::disk('public')->putFileAs(
                'avatars',
                $file,
                Str::uuid().'.'.$ext
            );
        }

        unset($validated['avatar'], $validated['remove_avatar']);
        $user->update($validated);

        return back()->with('success', __('Profil mis à jour avec succès.'));
    }

    public function editPassword(): InertiaResponse
    {
        return Inertia::render('Advertiser/Password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
                'regex:/[@_-]/',
            ],
        ], [
            'password.regex' => __('Le mot de passe doit contenir au moins un caractère spécial parmi @, _ ou -.'),
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', __('Mot de passe modifié avec succès.'));
    }
}
