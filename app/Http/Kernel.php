<?php

namespace App\Http;

use App\Docs\ResponseHeader;
use App\Docs\Schemas\JsonSchema;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\SentryContext;
use App\Http\Middleware\TrimStrings;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

#[ResponseHeader(
    name: 'Content-Type',
    description: 'Type of response',
    schema: new JsonSchema(),
    required: true,
)]
class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @inerhitDoc
     */
    protected $middleware = [
        CheckForMaintenanceMode::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
        HandleCors::class,
        SentryContext::class,
        TrustProxies::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @inerhitDoc
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            SubstituteBindings::class,
        ],

        'api' => [
            SubstituteBindings::class,
            EnsureFrontendRequestsAreStateful::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * @inerhitDoc
     */
    protected $middlewareAliases = [
        'auth'       => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'bindings'   => SubstituteBindings::class,
        'can'        => Authorize::class,
        'throttle'   => ThrottleRequests::class,
        'abilities'  => CheckAbilities::class,
        'ability'    => CheckForAnyAbility::class,
        'signed'     => ValidateSignature::class,
    ];

    /**
     * Returns list of global middleware
     * Used by documentation generation system
     * @return string[]
     */
    final public function getMiddleware(): array
    {
        return $this->middleware;
    }
}
