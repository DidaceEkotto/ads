<?php

namespace App\Livewire\Admin;

use App\Enums\CampaignStatus;
use App\Enums\SiteStatus;
use App\Enums\SocialPlacementStatus;
use App\Models\Campaign;
use App\Models\Site;
use App\Models\SocialAccount;
use App\Models\SocialPlacement;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('File de validation — CM-Ads Admin')]
class ValidationQueue extends Component
{
    public function approveSite(int $id): void
    {
        Site::query()->findOrFail($id)->update([
            'status' => SiteStatus::Approved,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);
        session()->flash('success', __('Site approuvé.'));
    }

    public function rejectSite(int $id): void
    {
        Site::query()->findOrFail($id)->update([
            'status' => SiteStatus::Rejected,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);
        session()->flash('success', __('Site rejeté.'));
    }

    public function approveCampaign(int $id): void
    {
        Campaign::query()->findOrFail($id)->update([
            'status' => CampaignStatus::Active,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);
        session()->flash('success', __('Campagne activée.'));
    }

    public function rejectCampaign(int $id): void
    {
        Campaign::query()->findOrFail($id)->update([
            'status' => CampaignStatus::Rejected,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);
        session()->flash('success', __('Campagne rejetée.'));
    }

    public function approveSocialAccount(int $id): void
    {
        SocialAccount::query()->findOrFail($id)->update(['status' => 'approved']);
        session()->flash('success', __('Compte social approuvé.'));
    }

    public function approveSocialPlacement(int $id): void
    {
        SocialPlacement::query()->findOrFail($id)->update(['status' => SocialPlacementStatus::Active]);
        session()->flash('success', __('Post social validé.'));
    }

    public function render()
    {
        return view('livewire.admin.validation-queue', [
            'sites' => Site::query()->with('publisher')->where('status', SiteStatus::Pending)->latest()->get(),
            'campaigns' => Campaign::query()->with('advertiser')->where('status', CampaignStatus::PendingReview)->latest()->get(),
            'socialAccounts' => SocialAccount::query()->with('publisher')->where('status', 'pending')->latest()->get(),
            'socialPlacements' => SocialPlacement::query()->with(['campaign', 'socialAccount'])->where('status', SocialPlacementStatus::PendingReview)->latest()->get(),
        ]);
    }
}
