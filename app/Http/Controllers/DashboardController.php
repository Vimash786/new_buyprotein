<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Cart;
use App\Models\Category;
use App\Models\orders;
use App\Models\products;
use App\Models\ProductVariantCombination;
use App\Models\Sellers;
use App\Models\Wishlist;
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

            $product = products::with(['subCategory', 'category', 'seller', 'images', 'variants', 'variantCombinations'])->findOrFail($id);
            $relatedProducts = products::where('category_id', $product->category_id)
                ->where('id', '!=', $id)
                ->limit(10)
                ->get();

            return view('shop.product-details', compact('product', 'relatedProducts'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Invalid product ID.');
        }
    }

    public function userAccount()
    {
        if (Auth::user() != null) {
            return view('shop.user-account');
        } else {
            return redirect()->route('login');
        }
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_option_ids' => 'nullable|array'
        ]);

        $product = products::findOrFail($request->product_id);
        $variantOptionIds = $request->variant_option_ids ? array_map('intval', $request->variant_option_ids) : [];

        $price = $this->calculatePrice($product, $variantOptionIds, $request->variant_option_ids);

        $userId = Auth::id();

        Cart::create([
            'user_id' => $userId,
            'product_id' => $product->id,
            'variant_option_ids' => $variantOptionIds,
            'quantity' => $request->quantity,
            'price' => $price
        ]);

        return response()->json(['status' => 'success']);
    }

    private function calculatePrice($product, $requestVariantOptionIds)
    {
        if ($requestVariantOptionIds) {
            $requestOptionIds = array_values($requestVariantOptionIds);
            $normalizedIds = array_map('intval', $requestOptionIds);
            sort($normalizedIds);

            $allCombinations = ProductVariantCombination::where('product_id', $product->id)->get();

            $matchingCombination = $allCombinations->first(function ($comb) use ($normalizedIds) {
                $combOptions = $comb->variant_options;
                return !array_diff($normalizedIds, $combOptions);
            });

            if ($matchingCombination) {
                return $matchingCombination->regular_user_final_price;
            }
        }

        return $product->regular_user_final_price;
    }

    public function removeCart($id)
    {
        $cartItem = Cart::findOrFail($id);
        $cartItem->delete();

        $userId = Auth::id();
        $cartItems = Cart::where('user_id', $userId)->get();
        $totalPrice = 0;

        foreach ($cartItems as $item) {
            $product = Products::find($item->product_id);
            if ($product) {
                $totalPrice += $product->price * $item->quantity;
            }
        }

        return response()->json([
            'success' => true,
            'totalPrice' => $totalPrice,
            'cartCount' => $cartItems->count()
        ]);
    }

    public function wishList()
    {
        $wishlistData = Wishlist::with('product')->where('user_id', Auth::user()->id)->get();

        return view('shop.wishlist', compact('wishlistData'));
    }

    public function addToWishList(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_option_ids' => 'nullable|array'
        ]);

        $product = products::findOrFail($request->product_id);
        $variantOptionIds = $request->variant_option_ids ? array_map('intval', $request->variant_option_ids) : [];

        $price = $this->calculatePrice($product, $variantOptionIds, $request->variant_option_ids);

        $userId = Auth::id();

        Wishlist::create([
            'user_id' => $userId,
            'product_id' => $product->id,
            'variant_option_ids' => $variantOptionIds,
            'quantity' => $request->quantity,
            'price' => $price
        ]);

        return response()->json(['status' => 'success']);
    }

    public function updateQuantity(Request $request)
    {
        if ($request->has('cart')) {
            $wishlist = Cart::findOrFail($request->id);
        } else {
            $wishlist = Wishlist::findOrFail($request->id);
        }

        $newQuantity = max(1, (int) $request->quantity);

        $wishlist->quantity = $newQuantity;
        $wishlist->save();

        $subtotal = $wishlist->price * $newQuantity;
        $totalAmount = 0;

        if ($request->has('cart')) {
            $alltotal = Cart::where('user_id', Auth::user()->id)->get();
            foreach ($alltotal as $sum) {
                $totalAmount += $sum->price * $sum->quantity;
            }
        }

        return response()->json([
            'success' => true,
            'quantity' => $newQuantity,
            'subtotal' => $subtotal,
            'total' => $totalAmount,
            'item_id' => $wishlist->id
        ]);
    }

    public function removeWishlist(Request $request)
    {
        if ($request->has('cart')) {
            $wishlist = Cart::findOrFail($request->id);
        } else {
            $wishlist = Wishlist::findOrFail($request->id);
        }

        if ($wishlist->user_id !== Auth::user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $wishlist->delete();

        return response()->json(['success' => true, 'id' => $request->id]);
    }

    public function wishToCart(Request $request)
    {
        $itemId = $request->input('item_id');
        $wishlistItem = Wishlist::where('id', $itemId)->where('user_id', Auth::user()->id)->first();

        if (!$wishlistItem) {
            return response()->json(['success' => false, 'message' => 'Item not found in wishlist.']);
        }

        $cartItem = Cart::create([
            'user_id' => Auth::user()->id,
            'product_id' => $wishlistItem->product_id,
            'variant_option_ids' => $wishlistItem->variant_option_ids,
            'quantity' => $wishlistItem->quantity,
            'price' => $wishlistItem->price
        ]);

        $wishlistItem->delete();

        return response()->json(['success' => true, 'message' => 'Item moved to cart.']);
    }

    public function cart()
    {
        $cartData = Cart::with('product')->where('user_id', Auth::user()->id)->get();

        return view('shop.cart', compact('cartData'));
    }

    public function checkout()
    {
        $cartData = Cart::with('product')->where('user_id', Auth::user()->id)->get();

        return view('shop.checkout', compact('cartData'));
    }

    public function aboutUs()
    {

        return view('informativePages.about');
    }

    public function termCondition()
    {

        return view('informativePages.terms-condition');
    }

    public function contact()
    {
        return view('informativePages.contact');
    }
}
