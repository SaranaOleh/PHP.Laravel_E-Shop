<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiAuth
{
    public function handle($request, Closure $next)
    {
        try {

            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'user_not_found', 'allowed' => false]);
            }

        } catch (TokenExpiredException $e) {

            return response()->json(['error' => $e->getMessage(), 'allowed' => false]);

        } catch (TokenInvalidException $e) {

            return response()->json(['error' => $e->getMessage(), 'allowed' => false]);

        } catch (JWTException $e) {

            return response()->json(['error' => $e->getMessage(), 'allowed' => false]);

        }

        return $next($request);
    }
}
