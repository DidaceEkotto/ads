<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('Paramètres Plateforme — CM-Ads Admin')]
class PlatformSettings extends Component
{
    use WithFileUploads;

    // Identité, Contact & SEO
    public string $siteName = 'CM-Ads';

    public string $siteDescription = '';

    public string $siteKeywords = '';

    public string $siteEmail = '';

    public string $sitePhone = '';

    public string $sitePostalBox = '';

    public $siteLogo = null;

    public $seoImage = null;

    public ?string $currentLogoUrl = null;

    public ?string $currentSeoImageUrl = null;

    // Maintenance
    public bool $maintenanceMode = false;

    public string $maintenanceMessage = '';

    // Tarification & Commissions
    public int $publisherSharePercent = 70;

    public int $minPayoutXaf = 5000;

    public int $earningsHoldDays = 14;

    public int $dailyCampaignCostXaf = 2000;

    public int $cpcUnitPriceXaf = 50;

    public int $viewUnitPriceXaf = 3;

    public string $supportWhatsapp = '';

    public function mount(): void
    {
        $this->siteName = (string) Setting::getValue('site_name', 'CM-Ads');
        $this->siteDescription = (string) Setting::getValue('site_description', '');
        $this->siteKeywords = (string) Setting::getValue('site_keywords', '');
        $this->siteEmail = (string) Setting::getValue('site_email', '');
        $this->sitePhone = (string) Setting::getValue('site_phone', '');
        $this->sitePostalBox = (string) Setting::getValue('site_postal_box', '');

        $logoPath = (string) Setting::getValue('site_logo_path', '');
        $seoPath = (string) Setting::getValue('seo_image_path', '');
        $this->currentLogoUrl = $logoPath !== '' ? Storage::disk('public')->url($logoPath) : null;
        $this->currentSeoImageUrl = $seoPath !== '' ? Storage::disk('public')->url($seoPath) : null;

        $this->maintenanceMode = (bool) Setting::getValue('maintenance_mode', false);
        $this->maintenanceMessage = (string) Setting::getValue('maintenance_message', 'Notre plateforme est actuellement en maintenance.');

        $this->publisherSharePercent = (int) Setting::getValue('publisher_share_percent', 70);
        $this->minPayoutXaf = (int) Setting::getValue('min_payout_xaf', 5000);
        $this->earningsHoldDays = (int) Setting::getValue('earnings_hold_days', 14);
        $this->dailyCampaignCostXaf = (int) Setting::getValue('daily_campaign_cost_xaf', 2000);
        $this->cpcUnitPriceXaf = (int) Setting::getValue('cpc_unit_price_xaf', 50);
        $this->viewUnitPriceXaf = (int) Setting::getValue('view_unit_price_xaf', 3);
        $this->supportWhatsapp = (string) Setting::getValue('support_whatsapp', '');
    }

    public function save(): void
    {
        $this->validate([
            'siteName' => ['required', 'string', 'max:100'],
            'siteDescription' => ['nullable', 'string', 'max:500'],
            'siteKeywords' => ['nullable', 'string', 'max:255'],
            'siteEmail' => ['nullable', 'email', 'max:150'],
            'sitePhone' => ['nullable', 'string', 'max:30'],
            'sitePostalBox' => ['nullable', 'string', 'max:100'],
            'siteLogo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:512'],
            'seoImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'maintenanceMode' => ['boolean'],
            'maintenanceMessage' => ['nullable', 'string', 'max:500'],
            'publisherSharePercent' => ['required', 'integer', 'min:10', 'max:95'],
            'minPayoutXaf' => ['required', 'integer', 'min:500'],
            'earningsHoldDays' => ['required', 'integer', 'min:1', 'max:60'],
            'dailyCampaignCostXaf' => ['required', 'integer', 'min:500'],
            'cpcUnitPriceXaf' => ['required', 'integer', 'min:5'],
            'viewUnitPriceXaf' => ['required', 'integer', 'min:1'],
            'supportWhatsapp' => ['required', 'url', 'max:255'],
        ]);

        if ($this->siteLogo) {
            $oldLogo = (string) Setting::getValue('site_logo_path', '');
            if ($oldLogo !== '') {
                Storage::disk('public')->delete($oldLogo);
            }
            $ext = $this->siteLogo->guessExtension() ?: 'png';
            $logoPath = $this->siteLogo->storeAs('branding', 'logo-'.Str::random(8).'.'.$ext, 'public');
            Setting::setValue('site_logo_path', $logoPath, 'string');
            $this->currentLogoUrl = Storage::disk('public')->url($logoPath);
            $this->siteLogo = null;
        }

        if ($this->seoImage) {
            $oldSeo = (string) Setting::getValue('seo_image_path', '');
            if ($oldSeo !== '') {
                Storage::disk('public')->delete($oldSeo);
            }
            $ext = $this->seoImage->guessExtension() ?: 'jpg';
            $seoPath = $this->seoImage->storeAs('branding', 'seo-'.Str::random(8).'.'.$ext, 'public');
            Setting::setValue('seo_image_path', $seoPath, 'string');
            $this->currentSeoImageUrl = Storage::disk('public')->url($seoPath);
            $this->seoImage = null;
        }

        Setting::setValue('site_name', $this->siteName, 'string');
        Setting::setValue('site_description', $this->siteDescription, 'string');
        Setting::setValue('site_keywords', $this->siteKeywords, 'string');
        Setting::setValue('site_email', $this->siteEmail, 'string');
        Setting::setValue('site_phone', $this->sitePhone, 'string');
        Setting::setValue('site_postal_box', $this->sitePostalBox, 'string');
        Setting::setValue('maintenance_mode', $this->maintenanceMode, 'boolean');
        Setting::setValue('maintenance_message', $this->maintenanceMessage, 'string');

        Setting::setValue('publisher_share_percent', $this->publisherSharePercent, 'integer');
        Setting::setValue('min_payout_xaf', $this->minPayoutXaf, 'integer');
        Setting::setValue('earnings_hold_days', $this->earningsHoldDays, 'integer');
        Setting::setValue('daily_campaign_cost_xaf', $this->dailyCampaignCostXaf, 'integer');
        Setting::setValue('cpc_unit_price_xaf', $this->cpcUnitPriceXaf, 'integer');
        Setting::setValue('view_unit_price_xaf', $this->viewUnitPriceXaf, 'integer');
        Setting::setValue('support_whatsapp', $this->supportWhatsapp, 'string');

        session()->flash('success', __('Paramètres enregistrés avec succès.'));
    }

    public function render()
    {
        return view('livewire.admin.platform-settings');
    }
}
