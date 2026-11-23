<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration de la géolocalisation IP (GeoIP2 MaxMind + Fallback HTTP)
    |--------------------------------------------------------------------------
    */
    'mmdb_path' => env('GEOIP_MMDB_PATH', storage_path('app/geoip/GeoLite2-City.mmdb')),
    'http_fallback' => env('GEOIP_HTTP_FALLBACK', true),
    'default_country' => env('GEOIP_DEFAULT_COUNTRY', 'Cameroun'),
    'default_city' => env('GEOIP_DEFAULT_CITY', 'Douala'),
];
