<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Запросы из публичных туннелей (demo) обслуживаем в безопасном режиме:
 * без отладочных страниц с раскрытием кода/конфига.
 */
class PublicSafeMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (str_ends_with($request->getHost(), 'trycloudflare.com')) {
            config([
                'app.debug'                => false,
                'app.registration_enabled' => false,
            ]);
        }

        return $next($request);
    }
}
