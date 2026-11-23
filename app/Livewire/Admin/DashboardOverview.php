<?php

namespace App\Livewire\Admin;

use App\Enums\CampaignStatus;
use App\Enums\PayoutStatus;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\FraudFlag;
use App\Models\Payout;
use App\Models\Site;
use App\Models\StatsDaily;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Administration — CM-Ads')]
class DashboardOverview extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard-overview', [
            'advertisersCount' => User::query()->where('role', UserRole::Advertiser)->count(),
            'publishersCount' => User::query()->where('role', UserRole::Publisher)->count(),
            'platformRevenueXaf' => (int) StatsDaily::query()->sum('platform_fee_xaf'),
            'totalImpressions' => (int) StatsDaily::query()->sum('impressions_count'),
            'totalValidClicks' => (int) StatsDaily::query()->sum('valid_clicks_count'),
            'pendingSitesCount' => Site::query()->where('status', SiteStatus::Pending)->count(),
            'pendingCampaignsCount' => Campaign::query()->where('status', CampaignStatus::PendingReview)->count(),
            'pendingPayoutsCount' => Payout::query()->where('status', PayoutStatus::Pending)->count(),
            'unresolvedFraudCount' => FraudFlag::query()->where('is_resolved', false)->count(),
            'topCampaigns' => Campaign::query()->orderByDesc('spent_xaf')->take(5)->get(),
            'recentSites' => Site::query()->with('publisher')->latest()->take(5)->get(),
        ]);
    }
}
