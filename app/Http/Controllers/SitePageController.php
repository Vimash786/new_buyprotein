<?php

namespace App\Http\Controllers;

use App\Models\SitePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SitePageController extends Controller
{
    /**
     * Display the specified site page.
     */
    public function show(string $slug): View
    {
        $page = SitePage::active()->where('slug', $slug)->firstOrFail();
        
        return view('pages.site-page', compact('page'));
    }

    /**
     * Display a page by type.
     */
    public function showByType(string $type): View
    {
        $page = SitePage::active()->byType($type)->firstOrFail();
        
        return view('pages.site-page', compact('page'));
    }
}
