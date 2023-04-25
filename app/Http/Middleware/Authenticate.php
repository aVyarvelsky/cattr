<?php

namespace App\Http\Middleware;

use App\Docs\RequestHeader;
use App\Docs\Schemas\JsonSchema;
use App\Exceptions\Entities\AuthorizationException;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Lang;

#[RequestHeader(
    name: 'Authorization',
    description: 'Authorization string',
    schema: new JsonSchema(),
    required: true,
    shouldMask: true,
)]
class Authenticate extends BaseAuthenticate
{
    public const DEFAULT_USER_LANGUAGE = 'en';

    final public function handle($request, Closure $next, ...$guards): mixed
    {
        $this->authenticate($request, $guards);

        if (!$request->user()->active) {
            $request->user()->tokens()->whereId($request->user()->currentAccessToken()->id)->delete();
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_USER_DISABLED);
        }

        Lang::setLocale($request->user()->user_language ?: self::DEFAULT_USER_LANGUAGE);
        return $next($request);
    }
}
