<?php

namespace App\Livewire\Admin;

use App\Models\FraudFlag;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Moniteur Anti-Fraude — CM-Ads Admin')]
class FraudMonitor extends Component
{
    public function resolve(int $id): void
    {
        FraudFlag::query()->findOrFail($id)->update(['is_resolved' => true]);
        session()->flash('success', __('Alerte marquée comme traitée.'));
    }

    public function render()
    {
        return view('livewire.admin.fraud-monitor', [
            'flags' => FraudFlag::query()->with(['campaign', 'publisher'])->latest()->take(50)->get(),
        ]);
    }
}
