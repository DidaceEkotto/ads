<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Utilisateurs — CM-Ads Admin')]
class UserManagement extends Component
{
    public string $roleFilter = '';

    public function toggleActive(int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        if ($user->id === auth()->id()) {
            return;
        }

        $user->update(['is_active' => ! $user->is_active]);
        session()->flash('success', __('Statut de l\'utilisateur mis à jour.'));
    }

    public function render()
    {
        $query = User::query()->with('wallet')->latest();
        if ($this->roleFilter !== '') {
            $query->where('role', $this->roleFilter);
        }

        return view('livewire.admin.user-management', [
            'users' => $query->take(50)->get(),
        ]);
    }
}
