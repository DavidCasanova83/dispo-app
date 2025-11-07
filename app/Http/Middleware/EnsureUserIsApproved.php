<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && !Auth::user()->isApproved()) {
            Auth::logout();
            
            return redirect()->route('login')
                ->with('error', 'Votre compte est en attente d\'approbation par un administrateur.');
        }
        
        return $next($request);
    }
}
