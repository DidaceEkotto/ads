<?php

namespace App\Jobs;

use App\Models\Impression;
use App\Services\Geo\GeoLocationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * Enregistre une impression publicitaire de manière asynchrone.
 */
class ProcessAdImpressionJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{
     *   campaign_id: int,
     *   creative_id: int,
     *   site_id: int|null,
     *   ad_zone_id: int|null,
     *   publisher_id: int,
     *   visitor_hash: string,
     *   ip_address: string,
     *   city: string|null,
     *   device_type: string
     * }  $data
     */
    public function __construct(public array $data) {}

    public function handle(GeoLocationService $geo): void
    {
        $location = (! empty($this->data['country']) && ! empty($this->data['city']))
            ? ['country' => $this->data['country'], 'city' => $this->data['city']]
            : $geo->locate($this->data['ip_address'] ?? null);

        Impression::query()->create([
            'campaign_id' => $this->data['campaign_id'],
            'creative_id' => $this->data['creative_id'],
            'site_id' => $this->data['site_id'],
            'ad_zone_id' => $this->data['ad_zone_id'],
            'publisher_id' => $this->data['publisher_id'],
            'visitor_hash' => $this->data['visitor_hash'],
            'ip_address' => $this->data['ip_address'],
            'country' => $location['country'],
            'city' => $location['city'],
            'device_type' => $this->data['device_type'],
            'cost_xaf' => 0,
            'created_at' => now(),
        ]);

        // Compteur rapide en cache/Redis pour le suivi temps réel
        $dateKey = now()->format('Y-m-d');
        Cache::increment("rt_imp:{$dateKey}:c:{$this->data['campaign_id']}");
    }
}
