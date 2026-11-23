<?php

namespace App\Services\Geo;

use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Service de géolocalisation IP (MaxMind GeoLite2 local en priorité, puis fallback HTTP mis en cache).
 */
class GeoLocationService
{
    /**
     * Retourne le pays et la ville correspondant à une adresse IP.
     *
     * @return array{country: string, city: string}
     */
    public function locate(?string $ip): array
    {
        $default = [
            'country' => (string) config('geoip.default_country', 'Cameroun'),
            'city' => (string) config('geoip.default_city', 'Douala'),
        ];

        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $default;
        }

        return Cache::remember("geoip:{$ip}", now()->addHours(24), function () use ($ip, $default): array {
            $mmdbPath = (string) config('geoip.mmdb_path');

            if ($mmdbPath !== '' && is_file($mmdbPath) && class_exists(Reader::class)) {
                try {
                    $reader = new Reader($mmdbPath);
                    $record = $reader->city($ip);

                    return [
                        'country' => $record->country->names['fr'] ?? $record->country->name ?? $default['country'],
                        'city' => $record->city->names['fr'] ?? $record->city->name ?? $default['city'],
                    ];
                } catch (Throwable) {
                    // Fallback HTTP ci-dessous si l'IP est absente de la base locale
                }
            }

            if (config('geoip.http_fallback', true)) {
                try {
                    $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}", [
                        'fields' => 'status,country,city',
                        'lang' => 'fr',
                    ]);

                    if ($response->ok() && $response->json('status') === 'success') {
                        return [
                            'country' => (string) ($response->json('country') ?: $default['country']),
                            'city' => (string) ($response->json('city') ?: $default['city']),
                        ];
                    }
                } catch (Throwable) {
                    // Tolérance aux pannes réseau
                }
            }

            return $default;
        });
    }
}
