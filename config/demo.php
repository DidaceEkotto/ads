<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mode Démo / Sandbox
    |--------------------------------------------------------------------------
    | Active les routes /demo et la connexion rapide en 1 clic pour tester
    | la plateforme localement sans agrégateur externe. Toujours false en prod.
    */
    'enabled' => env('APP_DEMO_MODE', false),
];
