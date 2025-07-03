<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\orders;
use App\Models\products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $categories = Category::limit(10)->get();
        $productCounts = orders::select('product_id', DB::raw('count(*) as total'))->groupBy('product_id')->orderBy('product_id')->get();
        $productIds = $productCounts->pluck('product_id');
        $products = products::with(['subCategory', 'category'])->whereIn('id', $productIds)->get();
        
        return view('dashboard', compact('categories', 'products'));
    }
}
