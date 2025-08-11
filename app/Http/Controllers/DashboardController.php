<?php

namespace App\Http\Controllers;

use App\Mail\BulkOrder as MailBulkOrder;
use App\Models\Banner;
use App\Models\BillingDetail;
use App\Models\Blog;
use App\Models\BlogComment;
use App\Models\BulkOrder;
use App\Models\Cart;
use Illuminate\Support\Facades\Log;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\CouponAssignment;
use App\Models\orders;
use App\Models\Policy;
use App\Models\products;
use App\Models\ProductVariantCombination;
use App\Models\ProductVariantOption;
use App\Models\Reference;
use App\Models\Review;
use App\Models\Sellers;
use App\Models\User;
use App\Models\Wishlist;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', 1)->limit(10)->get();
        // $productCounts = orders::select('product_id', DB::raw('count(*) as total'))->groupBy('product_id')->orderBy('product_id')->get();
        // $productIds = $productCounts->pluck('product_id');
        // $products = products::with(['subCategory', 'category'])->whereIn('id', $productIds)->get();
        
        // Get unique products for each section to prevent duplicates
        $everyDayEssentials = products::with(['subCategory', 'category', 'images', 'variants', 'seller'])
            ->whereJsonContains('section_category', 'everyday_essential')
            ->where('super_status', 'approved')
            ->distinct()
            ->limit(12) // Increased from 10 to 12 for better display
            ->get();
            
        $populerProducts = products::with(['images', 'variants', 'seller'])
            ->whereJsonContains('section_category', 'popular_pick')
            ->where('super_status', 'approved')
            ->distinct()
            ->limit(8) // Increased from 6 to 8 for better display
            ->get();
            
        // If no popular picks found, get products with high sales/views as fallback
        if ($populerProducts->isEmpty()) {
            $populerProducts = products::with(['images', 'variants', 'seller'])
                ->where('super_status', 'approved')
                ->whereNotIn('id', $everyDayEssentials->pluck('id'))
                ->orderBy('created_at', 'desc') // or order by sales if available
                ->distinct()
                ->limit(8)
                ->get();
        }
            
        $latestProducts = products::with(['images', 'variants', 'seller'])
            ->orderBy('created_at', 'desc')
            ->where('super_status', 'approved')
            ->distinct()
            ->take(10) // Changed from 12 to 10
            ->get();
            
        // If no exclusive deals found with exclusions, try without exclusions
        $offers = products::with(['images', 'variants', 'seller'])
            ->whereJsonContains('section_category', 'exclusive_deal')
            ->where('super_status', 'approved')
            ->distinct()
            ->limit(8)
            ->get();
            
        // If still no results, get products with high discount percentage as fallback
        if ($offers->isEmpty()) {
            $offers = products::with(['images', 'variants', 'seller'])
                ->where('super_status', 'approved')
                ->where(function($query) {
                    $query->where(function($subQuery) {
                        // Check main product discounts
                        $subQuery->where('regular_user_discount', '>', 10)
                                 ->orWhere('gym_owner_discount', '>', 10)
                                 ->orWhere('shop_owner_discount', '>', 10);
                    })->orWhereHas('variantCombinations', function($variantQuery) {
                        // Check variant discounts
                        $variantQuery->where('regular_user_discount', '>', 10)
                                    ->orWhere('gym_owner_discount', '>', 10)
                                    ->orWhere('shop_owner_discount', '>', 10);
                    });
                })
                ->whereNotIn('id', $everyDayEssentials->pluck('id'))
                ->whereNotIn('id', $populerProducts->pluck('id'))
                ->whereNotIn('id', $latestProducts->pluck('id'))
                ->distinct()
                ->limit(8)
                ->get();
        }
        
        // Debug: Log the offers count for troubleshooting
        Log::info('Exclusive Deals Query Result: ' . $offers->count() . ' products found');
            
        $banners = Banner::where('status', 'active')->orderBy('created_at', 'desc')->get();
        $sellers = Sellers::where('status', 'approved')->limit(4)->get();

        return view('dashboard', compact('categories', 'everyDayEssentials', 'populerProducts', 'latestProducts', 'offers', 'banners', 'sellers'));
    }

    public function shop(Request $request, $type = null, $id = null)
    {
        try {
            $id = $id ? Crypt::decrypt($id) : null;
            $categories = Category::limit(10)->where('is_active', 1)->get();
            $brands = Sellers::where('status', 'approved')->limit(10)->get();

            $productsQuery = products::query()->with('seller')->where('super_status', 'approved');
            
            // Set page title based on type
            $pageTitle = 'Shop';
            $pageDescription = 'Browse our wide selection of products';

            if ($type == 'category' && $id) {
                $productsQuery->where('category_id', $id);
                $category = Category::find($id);
                $pageTitle = $category ? $category->name : 'Category Products';
                $pageDescription = $category ? "Browse products in {$category->name} category" : 'Browse category products';
            } elseif ($type == 'brand' && $id) {
                $productsQuery->where('seller_id', $id);
                $brand = Sellers::find($id);
                $pageTitle = $brand ? $brand->brand : 'Brand Products';
                $pageDescription = $brand ? "Browse products from {$brand->brand}" : 'Browse brand products';
            } elseif ($type == 'new-arrivals') {
                $productsQuery->orderBy('created_at', 'desc');
                $pageTitle = 'New Arrivals';
                $pageDescription = 'Check out our latest products';
            } elseif ($type == 'popular-picks') {
                $productsQuery->whereJsonContains('section_category', 'popular_pick');
                $pageTitle = 'Popular Picks';
                $pageDescription = 'Discover our most popular and trending products';
            } elseif ($type == 'discount-deals') {
                // Get products with discounts - check both main product and variants
                $productsQuery->where(function($query) {
                    $query->where(function($subQuery) {
                        // Check main product discounts
                        $subQuery->whereJsonContains('section_category', 'exclusive_deal')
                                 ->orWhere('regular_user_discount', '>', 50)
                                 ->orWhere('gym_owner_discount', '>', 50)
                                 ->orWhere('shop_owner_discount', '>', 50);
                    })->orWhereHas('variantCombinations', function($variantQuery) {
                        // Check variant discounts
                        $variantQuery->where('regular_user_discount', '>', 50)
                                    ->orWhere('gym_owner_discount', '>', 50)
                                    ->orWhere('shop_owner_discount', '>', 50);
                    });
                });
                $pageTitle = 'Discount Deals';
                $pageDescription = 'Get up to 50% off on selected products';
            }
            if ($request->filled('search')) {
                $search = $request->input('search');
                $productsQuery->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhereHas('category', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('seller', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        });
                });
            }
            
            $allProducts = $productsQuery->get();

            $filteredProducts = $allProducts->filter(function ($product) use ($request) {
                $min = $request->input('min_price');
                $max = $request->input('max_price');

                $finalPrice = $this->getFinalPriceValue($product);

                return (!$min || $finalPrice >= $min) && (!$max || $finalPrice <= $max);
            });

            $page = request()->get('page', 1);
            $perPage = 10;
            $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
                $filteredProducts->forPage($page, $perPage),
                $filteredProducts->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            // if ($request->filled('min_price') && $request->filled('max_price')) {
            //     $min = $request->input('min_price');
            //     $max = $request->input('max_price');
            //     $productsQuery->whereBetween('price', [$min, $max]);
            // }

            return view('shop.shop', [
                'categories' => $categories,
                'brands' => $brands,
                'products' => $paginated,
                'pageTitle' => $pageTitle,
                'pageDescription' => $pageDescription,
                'currentType' => $type
            ]);
        } catch (Exception $e) {
            Log::error('Shop route error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Invalid category or brand ID.');
        }
    }

    protected function getFinalPriceValue($product)
    {
        $user = Auth::user();
        $type = 'final';

        $variant = $product->variantCombinations->first();

        if ($user) {
            $role = $user->role;

            if ($variant) {
                return match ($role) {
                    'User' => $variant->regular_user_final_price,
                    'Gym Owner/Trainer/Influencer/Dietitian' => $variant->gym_owner_final_price,
                    'Shop Owner' => $variant->shop_owner_final_price,
                    default => $variant->regular_user_final_price
                };
            } else {
                return match ($role) {
                    'User' => $product->regular_user_final_price,
                    'Gym Owner/Trainer/Influencer/Dietitian' => $product->gym_owner_final_price,
                    'Shop Owner' => $product->shop_owner_final_price,
                    default => $product->regular_user_final_price
                };
            }
        } else {
            // guest
            return $variant
                ? $variant->regular_user_final_price
                : $product->regular_user_final_price;
        }
    }

    public function productDetails($id)
    {
        try {
            $id = Crypt::decrypt($id);

            $product = products::with(['subCategory', 'category', 'seller', 'images', 'variants', 'variantCombinations'])->findOrFail($id);
            $relatedProducts = products::where('category_id', $product->category_id)->where('id', '!=', $id)->where('super_status', 'approved')->limit(10)->get();
            $reviews = Review::where('product_id', $id)->get();
            $totalReviews = $reviews->count();
            $averageRating = $reviews->avg('rating');

            $ratingCounts = [];
            for ($i = 1; $i <= 5; $i++) {
                $ratingCounts[$i] = $reviews->where('rating', $i)->count();
            }

            return view('shop.product-details', compact('product', 'relatedProducts', 'reviews', 'totalReviews', 'averageRating', 'ratingCounts'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Invalid product ID.');
        }
    }

    public function userAccount()
    {
        if (Auth::user() != null) {
            $orders = orders::with(['orderSellerProducts', 'billingDetail', 'billingDetail.shippingAddress'])->where('user_id', Auth::user()->id)->get();

            return view('shop.user-account', compact('orders'));
        } else {
            return redirect()->route('login');
        }
    }

    public function updateUserDetails(Request $request)
    {
        $userId = Auth::user()->id;

        $user = User::find($userId);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('current_password') && $request->filled('new_password') && $request->filled('confirm_new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors([
                    'current_password' => 'Current password is incorrect.',
                ])->withInput();
            }

            if ($request->new_password !== $request->confirm_new_password) {
                return back()->withErrors([
                    'confirm_new_password' => 'New password and confirmation do not match.',
                ])->withInput();
            }

            $request->validate([
                'new_password' => 'required|string|min:8',
            ]);

            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return back()->with('success', 'Account details updated successfully.');
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_option_ids' => 'nullable|array',
            'quantity' => 'nullable|integer|min:1'
        ]);

        $product = products::findOrFail($request->product_id);
        $quantity = $request->quantity ?? 1;
        $variantData = ProductVariantCombination::where('product_id', $product->id)->first();

        if (!$request->has('variant_option_ids') && $variantData) {
            $varOption = ProductVariantOption::whereIn('id', $variantData->variant_options)->get();

            $collection = collect($varOption);

            $result = $collection->mapWithKeys(function ($item) {
                return [$item->product_variant_id => $item->id];
            });

            $arrayResult = $result->toArray();

            $variantOptionIds = $arrayResult ? array_map('intval', $arrayResult) : [];
        } else {
            $variantOptionIds = $request->variant_option_ids ? array_map('intval', $request->variant_option_ids) : [];
        }

        $price = $this->calculatePrice($product, $variantOptionIds);

        if (Auth::check()) {
            $userId = Auth::id();

            // Check if item already exists in cart
            $existingCartItem = Cart::where('user_id', $userId)
                ->where('product_id', $product->id)
                ->where('variant_option_ids', $variantOptionIds)
                ->first();

            if ($existingCartItem) {
                // Update quantity if item exists
                $existingCartItem->quantity += $quantity;
                $existingCartItem->save();
            } else {
                // Create new cart item
                Cart::create([
                    'user_id' => $userId,
                    'product_id' => $product->id,
                    'variant_option_ids' => $variantOptionIds,
                    'quantity' => $quantity,
                    'price' => $price
                ]);
            }

            // Calculate total cart count (sum of all quantities)
            $cartData = Cart::where('user_id', Auth::user()->id)->get();
            $countCart = $cartData->sum('quantity');
        } else {
            $cart = session()->get('cart', []);

            $itemKey = $product->id . '_' . implode('_', $variantOptionIds);

            if (isset($cart[$itemKey])) {
                // Update quantity if item exists
                $cart[$itemKey]['quantity'] += $quantity;
            } else {
                // Create new cart item
                $cart[$itemKey] = [
                    'product_id' => $product->id,
                    'variant_option_ids' => $variantOptionIds,
                    'quantity' => $quantity,
                    'price' => $price,
                    'name' => $product->name,
                ];
            }

            session()->put('cart', $cart);

            // Calculate total cart count (sum of all quantities)
            $countCart = array_sum(array_column($cart, 'quantity'));
        }

        return response()->json(['status' => 'success', 'cartCount' => $countCart]);
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
            'cartCount' => $cartItems->sum('quantity') // Sum of quantities, not count of items
        ]);
    }

    public function wishList()
    {
        $wishlistData = collect();

        if (Auth::check()) {
            // Authenticated user - get from database
            $wishlistData = Wishlist::with(['product', 'product.images'])->where('user_id', Auth::user()->id)->get();
        } else {
            // Guest user - get from session
            $sessionWishlist = session()->get('wishlist', []);
            
            foreach ($sessionWishlist as $key => $item) {
                $product = products::with('images')->find($item['product_id']);
                if ($product) {
                    $wishlistItem = (object) [
                        'id' => $key,
                        'product_id' => $item['product_id'],
                        'variant_option_ids' => $item['variant_option_ids'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'product' => $product,
                        'created_at' => $item['created_at'] ?? now()->toDateTimeString()
                    ];
                    $wishlistData->push($wishlistItem);
                }
            }
        }

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

        $price = $this->calculatePrice($product, $request->variant_option_ids);

        if (Auth::check()) {
            // Authenticated user - store in database
            $userId = Auth::id();

            // Check if item already exists in wishlist
            $existingWishlist = Wishlist::where('user_id', $userId)
                ->where('product_id', $product->id)
                ->where('variant_option_ids', json_encode($variantOptionIds))
                ->first();

            if ($existingWishlist) {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Product already in wishlist!',
                    'wishlistCount' => Wishlist::where('user_id', $userId)->count()
                ]);
            }

            Wishlist::create([
                'user_id' => $userId,
                'product_id' => $product->id,
                'variant_option_ids' => $variantOptionIds,
                'quantity' => $request->quantity ?? 1,
                'price' => $price,
            ]);

            $wishlistCount = Wishlist::where('user_id', $userId)->count();
        } else {
            // Guest user - store in session
            $wishlist = session()->get('wishlist', []);

            $itemKey = $product->id . '_' . implode('_', $variantOptionIds);

            // Check if item already exists in session wishlist
            if (isset($wishlist[$itemKey])) {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Product already in wishlist!',
                    'wishlistCount' => count($wishlist)
                ]);
            }

            $wishlist[$itemKey] = [
                'product_id' => $product->id,
                'variant_option_ids' => $variantOptionIds,
                'quantity' => $request->quantity ?? 1,
                'price' => $price,
                'created_at' => now()->toDateTimeString()
            ];

            session()->put('wishlist', $wishlist);
            $wishlistCount = count($wishlist);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product added to wishlist!',
            'wishlistCount' => $wishlistCount
        ]);
    }

    public function updateQuantity(Request $request)
    {
        $newQuantity = max(1, (int) $request->quantity);
        $totalAmount = 0;
        $subtotal = 0;
        $itemId = $request->id;

        if (Auth::check()) {
            // Authenticated user - update database
            if ($request->has('cart')) {
                $wishlist = Cart::findOrFail($request->id);
            } else {
                $wishlist = Wishlist::findOrFail($request->id);
            }

            $wishlist->quantity = $newQuantity;
            $wishlist->save();

            $subtotal = $wishlist->price * $newQuantity;

            if ($request->has('cart')) {
                $alltotal = Cart::where('user_id', Auth::user()->id)->get();
                foreach ($alltotal as $sum) {
                    $totalAmount += $sum->price * $sum->quantity;
                }
            }
            
            $itemId = $wishlist->id;
        } else {
            // Guest user - update session
            if ($request->has('cart')) {
                $cart = session()->get('cart', []);
                if (isset($cart[$request->id])) {
                    $cart[$request->id]['quantity'] = $newQuantity;
                    $subtotal = $cart[$request->id]['price'] * $newQuantity;
                    session()->put('cart', $cart);
                    
                    // Calculate total for all cart items
                    foreach ($cart as $item) {
                        $totalAmount += $item['price'] * $item['quantity'];
                    }
                }
            } else {
                $wishlist = session()->get('wishlist', []);
                if (isset($wishlist[$request->id])) {
                    $wishlist[$request->id]['quantity'] = $newQuantity;
                    $subtotal = $wishlist[$request->id]['price'] * $newQuantity;
                    session()->put('wishlist', $wishlist);
                }
            }
        }

        return response()->json([
            'success' => true,
            'quantity' => $newQuantity,
            'subtotal' => $subtotal,
            'total' => $totalAmount,
            'item_id' => $itemId
        ]);
    }

    public function removeWishlist(Request $request)
    {
        Log::info('removeWishlist called with:', [
            'id' => $request->id,
            'cart' => $request->cart,
            'has_cart' => $request->has('cart'),
            'auth_check' => Auth::check(),
            'all_data' => $request->all()
        ]);

        if (Auth::check()) {
            // Authenticated user - remove from database
            if ($request->has('cart')) {
                $wishlist = Cart::findOrFail($request->id);
            } else {
                $wishlist = Wishlist::findOrFail($request->id);
            }

            if ($wishlist->user_id !== Auth::user()->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $wishlist->delete();

            $wishlistCounter = Wishlist::where('user_id', Auth::user()->id)->get()->count();
            $cartItems = Cart::where('user_id', Auth::id())->get();
            $cartCounter = $cartItems->sum('quantity'); // Sum of quantities, not count of items

            $totalPrice = $cartItems->sum(function ($item) {
                return $item->price * $item->quantity;
            });
        } else {
            // Guest user - remove from session
            if ($request->has('cart')) {
                // Remove from cart session
                $cart = session()->get('cart', []);
                if (isset($cart[$request->id])) {
                    unset($cart[$request->id]);
                    session()->put('cart', $cart);
                }
                $cartCounter = array_sum(array_column($cart, 'quantity')); // Sum of quantities
                $totalPrice = array_sum(array_map(function($item) {
                    return $item['price'] * $item['quantity'];
                }, $cart));
                
                // Get wishlist data for counter
                $wishlist = session()->get('wishlist', []);
                $wishlistCounter = count($wishlist);
            } else {
                // Remove from wishlist session
                $wishlist = session()->get('wishlist', []);
                if (isset($wishlist[$request->id])) {
                    unset($wishlist[$request->id]);
                    session()->put('wishlist', $wishlist);
                }
                $wishlistCounter = count($wishlist);
                
                // Get cart data for counter
                $cart = session()->get('cart', []);
                $cartCounter = array_sum(array_column($cart, 'quantity')); // Sum of quantities
                $totalPrice = array_sum(array_map(function($item) {
                    return $item['price'] * $item['quantity'];
                }, $cart));
            }
        }

        return response()->json([
            'success' => true, 
            'id' => $request->id, 
            'wishlistCount' => $wishlistCounter, 
            'cartCounter' => $cartCounter, 
            'totalPrice' => $totalPrice
        ]);
    }

    public function wishToCart(Request $request)
    {
        $itemId = $request->input('item_id');
        
        if (Auth::check()) {
            // Authenticated user - get from database
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
        } else {
            // Guest user - handle session data
            $wishlist = session()->get('wishlist', []);
            
            if (!isset($wishlist[$itemId])) {
                return response()->json(['success' => false, 'message' => 'Item not found in wishlist.']);
            }
            
            $wishlistItem = $wishlist[$itemId];
            
            // Add to cart session
            $cart = session()->get('cart', []);
            $cartItemKey = $wishlistItem['product_id'] . '_' . implode('_', $wishlistItem['variant_option_ids']);
            
            $cart[$cartItemKey] = [
                'product_id' => $wishlistItem['product_id'],
                'variant_option_ids' => $wishlistItem['variant_option_ids'],
                'quantity' => $wishlistItem['quantity'],
                'price' => $wishlistItem['price']
            ];
            
            session()->put('cart', $cart);
            
            // Remove from wishlist session
            unset($wishlist[$itemId]);
            session()->put('wishlist', $wishlist);
        }

        return response()->json(['success' => true, 'message' => 'Item moved to cart.']);
    }

    public function cart()
    {
        if (Auth::check()) {
            $cartData = Cart::with(['product', 'product.images'])->where('user_id', Auth::user()->id)->get();
        } else {
            $sessionCart = session('cart', []);
            $cartData = [];

            foreach ($sessionCart as $item) {
                $product = products::with('images')->find($item['product_id']);

                if ($product) {
                    $cartData[] = (object)[
                        'id' => $item['product_id'] . '_' . implode('_', $item['variant_option_ids']),
                        'product' => $product,
                        'variant_option_ids' => $item['variant_option_ids'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ];
                }
            }

            $cartData = collect($cartData);
        }
        return view('shop.cart', compact('cartData'));
    }

    public function checkout()
    {
        try {
            $cartData = collect(); // initialize empty collection
            $billingAddress = [];

            if (Auth::check()) {
                $cartData = Cart::with('product')->where('user_id', Auth::user()->id)->get();
                $order = orders::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->first();

                if ($order) {
                    $orderId = $order->id;
                    $billingAddress = BillingDetail::where('order_id', $orderId)->get();
                }
            } else {
                // For guest users, ensure billingAddress is empty array
                $billingAddress = [];
                $sessionCart = session('cart', []);

                foreach ($sessionCart as $item) {
                    $product = Products::find($item['product_id']);

                    if ($product) {
                        $cartData->push((object)[
                            'id' => $item['product_id'] . '_' . implode('_', $item['variant_option_ids']),
                            'product' => $product,
                            'variant_option_ids' => $item['variant_option_ids'],
                            'quantity' => $item['quantity'],
                            'price' => $item['price'],
                        ]);
                    }
                }
            }

            return view('shop.checkout', compact('cartData', 'billingAddress'));
        } catch (Exception $e) {
            Log::error('Checkout error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function aboutUs()
    {

        return view('informativePages.about');
    }

    public function termCondition()
    {
        $terms = Policy::where('type', 'terms-conditions')->first();

        return view('informativePages.terms-condition', compact('terms'));
    }

    public function shippingPolicy()
    {
        $shipping_policy = Policy::where('type', 'shipping-policy')->first();

        return view('informativePages.shopping-policy', compact('shipping_policy'));
    }

    public function privacyPolicy()
    {
        $privacy_policy = Policy::where('type', 'privacy-policy')->first();

        return view('informativePages.privacy-policy', compact('privacy_policy'));
    }

    public function returnPolicy()
    {
        $return_policy = Policy::where('type', 'return-policy')->first();

        return view('informativePages.return-policy', compact('return_policy'));
    }

    public function contact()
    {
        return view('informativePages.contact');
    }

    public function contactSubmit(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        Contact::create($request->only('name', 'email', 'subject', 'message'));

        return redirect()->back()->with('success', 'Your message has been sent successfully!');
    }

    public function bulkOrder(Request $request)
    {
        $variantSelections = collect($request->all())
            ->filter(function ($value, $key) {
                return str_starts_with($key, 'variant_');
            })
            ->mapWithKeys(function ($value, $key) {
                $variantId = (int) str_replace('variant_', '', $key);
                return [$variantId => (int) $value];
            });

        $product = products::find($request->product);

        bulkOrder::create([
            'user_id' => Auth::user()->id,
            'seller_id' => $product->seller_id,
            'product_id' => $request->product,
            'variant_option_ids' => $variantSelections,
            'quantity' => $request->quantity
        ]);

        $seller = User::find($product->seller_id);

        Mail::to($seller->email)->send(new MailBulkOrder($seller, Auth::user(), $product, $request->quantity));

        return redirect()->back()->with('Bulk order is make successfully!');
    }

    public function reviewStore(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:255',
                'review' => 'required|string',
                'rating' => 'required|integer|min:1|max:5',
            ]
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            Review::create(
                [
                    'product_id' => $request->productId,
                    'user_id' => Auth::check() ? Auth::user()->id : 0, // Use 0 for guest users until migration is done
                    'name'   => $request->name,
                    'email'  => 'guest@example.com', // Temporary placeholder until migration is done
                    'review' => $request->review,
                    'rating' => $request->rating,
                ]
            );

            return redirect()->back()->with('success', 'Product Review is submitted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to submit review. Please try again.');
        }
    }

    public function blogs()
    {
        $blogs = Blog::paginate(8);

        return view('blogs.index', compact('blogs'));
    }

    public function blogDetails($id)
    {
        try {
            $blogId = Crypt::decrypt($id);
            $blog = Blog::find($blogId);
            $blogComments = BlogComment::with('user')->get();

            return view('blogs.details', compact('blog', 'blogComments'));
        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function blogComment(Request $request, $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'message' => 'required|string',
            ]
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        BlogComment::create([
            'blog_id' => $id,
            'user_id' => Auth::user()->id,
            'content' => $request->message,
        ]);

        return redirect()->back()->with('success', 'Review submitted successfully!');
    }

    public function applyCoupon(Request $request)
    {
        $couponData = Coupon::with('assignments')->where('code', $request->coupon)->first();
        $couponType = '';

        if (!$couponData) {
            $couponData = Reference::with('assignments')->where('code', $request->coupon)->first();
            $couponType = 'reference';
        }

        // Handle cart data for both authenticated and guest users
        if (Auth::check()) {
            $cartData = Cart::with(['product.seller'])->where('user_id', Auth::user()->id)->get();
            $user = Auth::user();
        } else {
            // For guest users, create a simpler cart structure from session
            $sessionCart = session('cart', []);
            $cartData = collect();
            
            foreach ($sessionCart as $item) {
                $product = Products::find($item['product_id']);
                if ($product) {
                    $cartData->push((object)[
                        'id' => $item['product_id'] . '_' . implode('_', $item['variant_option_ids']),
                        'product_id' => $item['product_id'],
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'product' => $product
                    ]);
                }
            }
            $user = null;
        }

        if ($couponData) {
            // For guest users, provide basic coupon support
            if (!Auth::check()) {
                if ($couponData->status == 'active' && Carbon::now()->between($couponData->starts_at, $couponData->expires_at) && $couponData->used_count <= $couponData->usage_limit) {
                    $subtotal = $cartData->sum(function ($item) {
                        return $item->price * $item->quantity;
                    });

                    $totalDiscount = 0;
                    if ($couponData->type == 'percentage') {
                        $totalDiscount = $subtotal * ($couponData->value / 100);
                        if (isset($couponData->maximum_discount) && $totalDiscount > $couponData->maximum_discount) {
                            $totalDiscount = $couponData->maximum_discount;
                        }
                    } else {
                        $totalDiscount = min($couponData->value, $subtotal);
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Coupon applied successfully!',
                        'total_discount' => round($totalDiscount, 2),
                        'matched_subtotal' => round($subtotal, 2),
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Coupon is expired or invalid.'
                    ]);
                }
            }

            // Existing logic for authenticated users follows...
            if ($couponData->status == 'active') {
                if (Carbon::now()->between($couponData->starts_at, $couponData->expires_at)) {
                    if ($couponData->used_count <= $couponData->usage_limit) {

                        $assignedUserIds = [];

                        if ($couponType == 'reference') {
                            $assignedUserIds = $couponData->assignments->where('assignable_type', 'user')->pluck('assignable_id')->toArray();

                            $matchingCartItems = $cartData;
                        } else {
                            if ($couponData->applicable_to != 'all_users' && $couponData->applicable_to != 'all_products') {

                                $assignedUserIds = $couponData->assignments->where('assignable_type', 'user')->pluck('assignable_id')->toArray();

                                $productIds = $couponData->assignments->pluck('assignable_id')->toArray();
                                $cartProductIds = $cartData->pluck('product_id')->toArray();

                                $matchedProductIds = array_intersect($cartProductIds, $productIds);

                                // working fine for products
                                $matchingCartItems = $cartData->whereIn('product_id', $matchedProductIds);

                                // working fine for user
                                if (Auth::check()) {
                                    $matchingCartItems = $cartData->whereIn('user_id', Auth::user()->id);
                                }
                            } else {
                                $matchingCartItems = $cartData;
                            }
                        }

                        $matchedSubtotal = $matchingCartItems->sum(function ($item) {
                            return $item->price * $item->quantity;
                        });

                        $perItemDiscounts = [];
                        $totalDiscount = 0;

                        if ($couponType == 'reference') {
                            if (Auth::check() && (in_array(Auth::id(), $assignedUserIds) || in_array('all_shop', $couponData->applicable_to) || in_array('all_gym', $couponData->applicable_to))) {
                                if ($couponData->type == 'percentage') {

                                    foreach ($matchingCartItems as $item) {
                                        $itemSubtotal = $item->price * $item->quantity;
                                        $itemDiscount = $itemSubtotal * ($couponData->applyer_discount / 100);

                                        $perItemDiscounts[] = [
                                            'cart_item_id' => $item->id,
                                            'product_id' => $item->product_id,
                                            'quantity' => $item->quantity,
                                            'item_subtotal' => $itemSubtotal,
                                            'discount' => round($itemDiscount, 2),
                                            'reference' => 'yes',
                                        ];

                                        $totalDiscount += $itemDiscount;
                                    }
                                    if (isset($couponData->maximum_discount) && $totalDiscount > $couponData->maximum_discount) {
                                        $totalDiscount = $couponData->maximum_discount;

                                        $perItemDiscounts = array_map(function ($item) use ($matchedSubtotal, $totalDiscount) {
                                            $proportional = ($item['item_subtotal'] / $matchedSubtotal) * $totalDiscount;
                                            $item['discount'] = round($proportional, 2);
                                            return $item;
                                        }, $perItemDiscounts);
                                    }
                                } else {
                                    $flat = $couponData->value;
                                    if ($flat > $matchedSubtotal) {
                                        $flat = $matchedSubtotal;
                                    }

                                    foreach ($matchingCartItems as $item) {
                                        $itemSubtotal = $item->price * $item->quantity;
                                        $itemDiscount = ($itemSubtotal / $matchedSubtotal) * $flat;

                                        $perItemDiscounts[] = [
                                            'cart_item_id' => $item->id,
                                            'product_id' => $item->product_id,
                                            'quantity' => $item->quantity,
                                            'item_subtotal' => $itemSubtotal,
                                            'discount' => round($itemDiscount, 2),
                                            'reference' => 'yes',
                                        ];

                                        $totalDiscount += $itemDiscount;
                                    }

                                    $totalDiscount = $flat;
                                }
                            }
                        } else {
                            if (in_array(Auth::id(), $assignedUserIds) || $matchingCartItems->isNotEmpty() || $couponData->applicable_to == 'all_users' || $couponData->applicable_to == 'all_products') {
                                if ($couponData->type == 'percentage') {
                                    foreach ($matchingCartItems as $item) {
                                        if ($item->product->seller->user_id == $couponData->created_by) {
                                            $itemSubtotal = $item->price * $item->quantity;
                                            $itemDiscount = $itemSubtotal * ($couponData->value / 100);

                                            $perItemDiscounts[] = [
                                                'cart_item_id' => $item->id,
                                                'product_id' => $item->product_id,
                                                'quantity' => $item->quantity,
                                                'item_subtotal' => $itemSubtotal,
                                                'discount' => round($itemDiscount, 2),
                                            ];

                                            $totalDiscount += $itemDiscount;
                                        }
                                    }
                                    if (isset($couponData->max_discount) && $totalDiscount > $couponData->max_discount) {
                                        $totalDiscount = $couponData->max_discount;

                                        $perItemDiscounts = array_map(function ($item) use ($matchedSubtotal, $totalDiscount) {
                                            $proportional = ($item['item_subtotal'] / $matchedSubtotal) * $totalDiscount;
                                            $item['discount'] = round($proportional, 2);
                                            return $item;
                                        }, $perItemDiscounts);
                                    }
                                } else {
                                    $flat = $couponData->value;
                                    if ($flat > $matchedSubtotal) {
                                        $flat = $matchedSubtotal;
                                    }

                                    foreach ($matchingCartItems as $item) {
                                        $itemSubtotal = $item->price * $item->quantity;
                                        $itemDiscount = ($itemSubtotal / $matchedSubtotal) * $flat;

                                        $perItemDiscounts[] = [
                                            'cart_item_id' => $item->id,
                                            'product_id' => $item->product_id,
                                            'quantity' => $item->quantity,
                                            'item_subtotal' => $itemSubtotal,
                                            'discount' => round($itemDiscount, 2),
                                        ];

                                        $totalDiscount += $itemDiscount;
                                    }

                                    $totalDiscount = $flat;
                                }
                            }
                        }

                        return response()->json([
                            'success' => true,
                            'message' => 'Coupon applied successfully!',
                            'total_discount' => round($totalDiscount, 2),
                            'matched_subtotal' => round($matchedSubtotal, 2),
                            'per_item_discounts' => $perItemDiscounts
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Coupon Limit is expired.'
                        ]);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Coupon is expired.'
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Coupon is expired.'
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code.'
            ]);
        }
    }
}
