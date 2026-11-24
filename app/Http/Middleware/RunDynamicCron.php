<?php

namespace App\Http\Middleware;

use App\Services\Automation\DynamicCronService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware terminable : déclenche les tâches planifiées après l'envoi de la réponse HTTP.
 */
class RunDynamicCron
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        // Exécution uniquement sur les requêtes GET classiques (pas d'impact sur les POST/webhooks)
        if (! $request->isMethod('GET')) {
            return;
        }

        app(DynamicCronService::class)->runDueTasks();
    }
}
