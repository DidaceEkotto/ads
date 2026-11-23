<?php

namespace Database\Seeders;

use App\Enums\AdZoneFormat;
use App\Enums\BillingModel;
use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Enums\SiteStatus;
use App\Enums\SocialPlacementStatus;
use App\Enums\SocialPlatform;
use App\Enums\UserRole;
use App\Models\AdZone;
use App\Models\AdvertiserProfile;
use App\Models\Campaign;
use App\Models\PaymentMethod;
use App\Models\PublisherProfile;
use App\Models\Site;
use App\Models\SocialAccount;
use App\Models\SocialPlacement;
use App\Models\StatsDaily;
use App\Models\TrackingLink;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoSeeder extends Seeder
{
    /**
     * Crée un environnement de démonstration complet (Annonceur, Éditeur, Admin,
     * campagnes actives, emplacement web, lien social et statistiques sur 7 jours).
     */
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        // Visuel PNG 1x1 léger pour les créatives démo
        $demoImagePath = 'creatives/demo/promo-douala.png';
        if (! Storage::disk('public')->exists($demoImagePath)) {
            $png1x1 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
            Storage::disk('public')->put($demoImagePath, $png1x1);
        }

        // 1. Annonceur Démo
        $advertiser = User::query()->firstOrCreate(
            ['email' => 'advertiser@demo.cm'],
            [
                'name' => 'Marc Boutique (Démo)',
                'phone' => '+237677111000',
                'role' => UserRole::Advertiser,
                'locale' => 'fr',
                'is_active' => true,
                'password' => 'Demo@2026!',
                'email_verified_at' => now(),
            ]
        );
        AdvertiserProfile::query()->firstOrCreate(
            ['user_id' => $advertiser->id],
            ['company_name' => 'Boutique Douala SARL', 'city' => 'Douala', 'whatsapp_number' => '+237677111000']
        );
        Wallet::query()->updateOrCreate(
            ['user_id' => $advertiser->id],
            ['available_balance_xaf' => 150000, 'total_spent_xaf' => 12500]
        );

        // 2. Éditeur Démo
        $publisher = User::query()->firstOrCreate(
            ['email' => 'publisher@demo.cm'],
            [
                'name' => 'Clarisse Média (Démo)',
                'phone' => '+237699222000',
                'role' => UserRole::Publisher,
                'locale' => 'fr',
                'is_active' => true,
                'password' => 'Demo@2026!',
                'email_verified_at' => now(),
            ]
        );
        PublisherProfile::query()->firstOrCreate(
            ['user_id' => $publisher->id],
            ['display_name' => 'Actu Cameroun', 'city' => 'Yaoundé', 'whatsapp_number' => '+237699222000']
        );
        Wallet::query()->updateOrCreate(
            ['user_id' => $publisher->id],
            ['available_balance_xaf' => 12500, 'pending_balance_xaf' => 4500, 'total_earned_xaf' => 17000]
        );
        PaymentMethod::query()->firstOrCreate(
            ['user_id' => $publisher->id, 'account_number' => '+237677000111'],
            ['provider' => 'mtn_momo', 'account_name' => 'Clarisse Média', 'is_default' => true, 'is_verified' => true]
        );

        // 3. Campagne Web CPC active
        $webCampaign = Campaign::query()->firstOrCreate(
            ['advertiser_id' => $advertiser->id, 'name' => 'Promo Rentrée Douala (Démo)'],
            [
                'type' => CampaignType::Web,
                'billing_model' => BillingModel::Cpc,
                'status' => CampaignStatus::Active,
                'total_budget_xaf' => 50000,
                'daily_budget_xaf' => 10000,
                'spent_xaf' => 2500,
                'unit_price_xaf' => 50,
            ]
        );
        $webCampaign->creatives()->firstOrCreate(
            ['title' => '-30% sur la livraison à Douala & Yaoundé'],
            [
                'body_text' => 'Commandez aujourd\'hui et recevez votre colis en 24h.',
                'destination_url' => 'https://example.cm/promo-rentree',
                'media_type' => 'image',
                'media_path' => $demoImagePath,
                'format' => AdZoneFormat::Square300x250->value,
                'file_size_bytes' => 12000,
                'is_active' => true,
            ]
        );

        // 4. Site & Emplacement Démo approuvés
        $site = Site::query()->firstOrCreate(
            ['domain' => 'actu-demo.cm'],
            [
                'publisher_id' => $publisher->id,
                'name' => 'Actu Cameroun Démo',
                'category' => 'actualites',
                'language' => 'fr',
                'status' => SiteStatus::Approved,
            ]
        );
        AdZone::query()->firstOrCreate(
            ['token' => 'demozone12345678'],
            [
                'site_id' => $site->id,
                'name' => 'Encart Article Principal',
                'format' => AdZoneFormat::Square300x250,
                'is_native' => true,
                'is_active' => true,
            ]
        );

        // 5. Campagne Sociale & Lien /c/demosocial01
        $socialCampaign = Campaign::query()->firstOrCreate(
            ['advertiser_id' => $advertiser->id, 'name' => 'Lancement App Mobile (Social Démo)'],
            [
                'type' => CampaignType::Social,
                'billing_model' => BillingModel::Cpc,
                'status' => CampaignStatus::Active,
                'total_budget_xaf' => 30000,
                'unit_price_xaf' => 75,
            ]
        );
        $socialCampaign->creatives()->firstOrCreate(
            ['title' => 'Découvrez la nouvelle app CM-Pay'],
            [
                'body_text' => 'Transférez sans frais au Cameroun #pub',
                'destination_url' => 'https://example.cm/app',
                'media_type' => 'image',
                'media_path' => $demoImagePath,
                'format' => 'social',
                'file_size_bytes' => 12000,
                'is_active' => true,
            ]
        );
        $socialAccount = SocialAccount::query()->firstOrCreate(
            ['platform' => SocialPlatform::Facebook, 'handle' => 'actucameroundemo'],
            [
                'publisher_id' => $publisher->id,
                'profile_url' => 'https://facebook.com/actucameroundemo',
                'followers_count' => 24500,
                'verification_code' => 'CMADS-DEMO01',
                'status' => 'approved',
            ]
        );
        $placement = SocialPlacement::query()->firstOrCreate(
            ['campaign_id' => $socialCampaign->id, 'social_account_id' => $socialAccount->id],
            [
                'publisher_id' => $publisher->id,
                'post_url' => 'https://facebook.com/actucameroundemo/posts/101',
                'has_disclosure_tag' => true,
                'status' => SocialPlacementStatus::Active,
                'published_at' => now()->subDays(2),
                'min_duration_ends_at' => now(),
            ]
        );
        TrackingLink::query()->firstOrCreate(
            ['code' => 'demosocial01'],
            [
                'campaign_id' => $socialCampaign->id,
                'publisher_id' => $publisher->id,
                'social_placement_id' => $placement->id,
                'is_active' => true,
            ]
        );

        // 6. Statistiques pré-agrégées sur 7 jours
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            StatsDaily::query()->updateOrCreate(
                [
                    'date' => $date,
                    'campaign_id' => $webCampaign->id,
                    'publisher_id' => $publisher->id,
                    'site_id' => $site->id,
                    'ad_zone_id' => 1,
                    'device_type' => 'mobile',
                ],
                [
                    'impressions_count' => 420 + ($i * 35),
                    'valid_clicks_count' => 18 + $i,
                    'invalid_clicks_count' => 2,
                    'adblock_hits_count' => 15,
                    'spend_xaf' => (18 + $i) * 50,
                    'publisher_earning_xaf' => (18 + $i) * 35,
                    'platform_fee_xaf' => (18 + $i) * 15,
                ]
            );
        }
    }
}
