<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Initialise les paramètres par défaut de la plateforme (marché camerounais).
     */
    public function run(): void
    {
        $defaults = [
            ['publisher_share_percent', 70, 'integer', 'Pourcentage reversé à l\'éditeur (défaut 70%)'],
            ['min_payout_xaf', 5000, 'integer', 'Montant minimum de retrait en FCFA'],
            ['min_deposit_xaf', 2000, 'integer', 'Montant minimum de recharge annonceur en FCFA'],
            ['earnings_hold_days', 14, 'integer', 'Durée de blocage des gains éditeur avant disponibilité (7 à 30 jours)'],
            ['referral_bonus_percent', 5, 'integer', 'Bonus de parrainage (%) sur les recharges des filleuls'],
            ['daily_campaign_cost_xaf', 2000, 'integer', 'Coût journalier d\'une campagne en FCFA'],
            ['cpc_unit_price_xaf', 50, 'integer', 'Prix unitaire d\'un clic sur lien en FCFA'],
            ['view_unit_price_xaf', 5, 'integer', 'Prix d\'une vue visible au scroll sur site en FCFA'],
            ['min_social_followers', 500, 'integer', 'Nombre minimum d\'abonnés pour un compte social éditeur'],
            ['social_post_min_hours', 48, 'integer', 'Durée minimale de maintien d\'un post sponsorisé (en heures)'],
            ['support_whatsapp', 'https://wa.me/237600000000', 'string', 'Lien WhatsApp du support client'],
            ['site_name', 'CM-Ads', 'string', 'Nom de la plateforme'],
            ['site_description', 'Plateforme publicitaire et réseau d\'affiliation pour le Cameroun.', 'string', 'Description SEO du site'],
            ['site_keywords', 'publicité cameroun, affiliation, mtn momo, orange money, douala, yaoundé', 'string', 'Mots-clés SEO'],
            ['site_email', 'contact@cm-ads.cm', 'string', 'Email officiel de contact'],
            ['site_phone', '+237690000000', 'string', 'Téléphone officiel'],
            ['site_postal_box', '', 'string', 'Boîte postale (facultatif)'],
            ['site_logo_path', '', 'string', 'Chemin du logo de la plateforme'],
            ['seo_image_path', '', 'string', 'Chemin de l\'image SEO / OpenGraph'],
            ['maintenance_mode', false, 'boolean', 'Active le mode maintenance (hors /admin)'],
            ['maintenance_message', 'Notre plateforme est actuellement en maintenance. Nous revenons très vite !', 'string', 'Message affiché en mode maintenance'],
        ];

        foreach ($defaults as [$key, $value, $type, $description]) {
            Setting::setValue($key, $value, $type, $description);
        }
    }
}
