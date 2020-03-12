<?php

namespace Ghustavh97\Larakey\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ghustavh97\Larakey\Exceptions\UnauthorizedException;

class RoleMiddleware
{
    /**
     * MIddleware handle.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|array $role
     *
     * @return mixed
     *
     * @throws \Ghustavh97\Larakey\Exceptions\UnauthorizedException
     */
    public function handle(Request $request, Closure $next, $role)
    {
        if (Auth::guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $roles = is_array($role)
            ? $role
            : explode('|', $role);

        if (! Auth::user()->hasAnyRole($roles)) {
            throw UnauthorizedException::forRoles($roles);
        }

        return $next($request);
    }
}
