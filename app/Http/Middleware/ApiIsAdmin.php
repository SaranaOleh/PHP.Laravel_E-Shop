<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiIsAdmin
{
    public function handle($request, Closure $next)
    {
        try{
            $user = JWTAuth::parseToken()->authenticate();
        }catch (JWTException $e){
            return response()->json(['allowed' => false]);
        }
        try{
            if($user->roles[0]->name !== 'admin'){
                return response()->json(['allowed' => false]);
            }
        }catch (\Exception $e){
            return response()->json(['allowed' => false]);
        }

        return $next($request);
    }
}
