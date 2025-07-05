<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\orders;
use App\Models\products;
use App\Models\Sellers;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $categories = Category::limit(10)->get();
        $productCounts = orders::select('product_id', DB::raw('count(*) as total'))->groupBy('product_id')->orderBy('product_id')->get();
        $productIds = $productCounts->pluck('product_id');
        $products = products::with(['subCategory', 'category'])->whereIn('id', $productIds)->get();
        $everyDayEssentials = products::with(['subCategory', 'category'])->where('section_category', 'everyday_essential')->limit(10)->get();
        $populerProducts = products::where('section_category', 'popular_pick')->limit(6)->get();
        $latestProducts = products::orderBy('created_at', 'desc')->take(12)->get();
        $offers = products::where('section_category', 'exclusive_deal')->limit(8)->get();

        return view('dashboard', compact('categories', 'products', 'everyDayEssentials', 'populerProducts', 'latestProducts', 'offers'));
    }

    public function shop($type = null, $id = null)
    {
        try {
            $id = $id ? Crypt::decrypt($id) : null;
            $categories = Category::limit(10)->get();
            $brands = Sellers::limit(10)->get();

            if ($type == 'category') {
                $products = products::where('category_id', $id)->paginate(10);
            } elseif ($type == 'brand') {
                $products = products::where('seller_id', $id)->paginate(10);
            } elseif ($type == 'new-arrivals') {
                $products = products::orderBy('created_at', 'desc')->limit(50)->paginate(10);
            }

            return view('shop.shop', compact('categories', 'products', 'brands'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Invalid category or brand ID.');
        }
    }
}
