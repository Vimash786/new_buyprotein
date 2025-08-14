<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RestrictToSuperAndSeller
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // If user is not authenticated, redirect to login
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check if user role is Super or Seller
        if (!in_array($user->role, ['Super', 'Seller'])) {
            // Redirect non-Super/Seller users to home page with error message
            return redirect()->route('home')->with('error', 'Access denied. Only Super administrators and Sellers can access the dashboard.');
        }
        
        return $next($request);
    }
}
