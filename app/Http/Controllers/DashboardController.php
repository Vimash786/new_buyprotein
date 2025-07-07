<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\orders;
use App\Models\products;
use App\Models\Sellers;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $banners = Banner::where('status', 'active')->orderBy('created_at', 'desc')->get();

        return view('dashboard', compact('categories', 'products', 'everyDayEssentials', 'populerProducts', 'latestProducts', 'offers', 'banners'));
    }

    public function shop(Request $request, $type = null, $id = null)
    {
        try {
            $id = $id ? Crypt::decrypt($id) : null;
            $categories = Category::limit(10)->get();
            $brands = Sellers::limit(10)->get();

            $productsQuery = products::query();

            if ($type == 'category' && $id) {
                $productsQuery->where('category_id', $id);
            } elseif ($type == 'brand' && $id) {
                $productsQuery->where('seller_id', $id);
            } elseif ($type == 'new-arrivals') {
                $productsQuery->orderBy('created_at', 'desc');
            }

            if ($request->filled('min_price') && $request->filled('max_price')) {
                $min = $request->input('min_price');
                $max = $request->input('max_price');
                $productsQuery->whereBetween('price', [$min, $max]);
            }

            $products = $productsQuery->paginate(10);

            return view('shop.shop', compact('categories', 'products', 'brands'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Invalid category or brand ID.');
        }
    }

    public function productDetails($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $product = products::with(['subCategory', 'category', 'seller', 'images'])->findOrFail($id);
            $relatedProducts = products::where('category_id', $product->category_id)
                ->where('id', '!=', $id)
                ->limit(10)
                ->get();

            return view('shop.product-details', compact('product', 'relatedProducts'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Invalid product ID.');
        }
    }

    public function userAccount() {
        if (Auth::user() != null) {

        } else {
            return redirect()->route('login');
        }
    }
}
