<?php

namespace App\Http\Middleware;

use App\Services\SwaggerService;
use Closure;
use Illuminate\Http\Request;

class RecordRequestForSwagger
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        app(SwaggerService::class, ['request' => $request, 'response' => $response])->processData();

        return $response;
    }
}
