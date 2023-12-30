<?php

namespace App\Http\Middleware;

use Closure;
use Log;

class BasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   
        if($request->getUser() == env('USER_OPEN', '') && $request->getPassword() == env('PASS_OPEN', '')){
            return $next($request);
        }else{
            if(env('SAVE_LOG', true)){
                Log::warning('auth-basic no autorizado: ',$request->header());
                Log::warning('IP: '.$request->ip().' user: '.$request->getUser().' pass: '.$request->getPassword());
            }

            return response('Unauthorized', 401);
        }
    }
}
