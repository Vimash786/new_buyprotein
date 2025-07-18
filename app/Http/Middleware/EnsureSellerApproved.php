<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Sellers;
use Symfony\Component\HttpFoundation\Response;

class EnsureSellerApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // If user is not authenticated, let the auth middleware handle it
        if (!$user) {
            return $next($request);
        }
        
        // If user is not a seller, allow access (admins, etc.)
        if ($user->role !== 'Seller') {
            return $next($request);
        }
        
        // Check if seller is approved
        $seller = Sellers::where('user_id', $user->id)->first();
        
        if (!$seller || $seller->status !== 'approved') {
            return redirect()->route('dashboard')->with('error', 'Your seller account needs to be approved before you can access this feature. Please contact the administrator.');
        }
        
        return $next($request);
    }
}
