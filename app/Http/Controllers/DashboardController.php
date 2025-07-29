<?php

namespace App\Http\Controllers;

use App\Mail\BulkOrder as MailBulkOrder;
use App\Models\Banner;
use App\Models\BillingDetail;
use App\Models\Blog;
use App\Models\BlogComment;
use App\Models\BulkOrder;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\CouponAssignment;
use App\Models\orders;
use App\Models\Policy;
use App\Models\products;
use App\Models\ProductVariantCombination;
use App\Models\ProductVariantOption;
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
        $everyDayEssentials = products::with(['subCategory', 'category', 'images', 'variants', 'seller'])->where('section_category', 'LIKE', '%"everyday_essential"%')->where('super_status', 'approved')->limit(10)->get();
        $populerProducts = products::with(['images', 'variants', 'seller'])->where('section_category', 'LIKE', '%"popular_pick"%')->where('super_status', 'approved')->limit(6)->get();
        $latestProducts = products::with(['images', 'variants', 'seller'])->orderBy('created_at', 'desc')->where('super_status', 'approved')->take(12)->get();
        $offers = products::with(['images', 'variants', 'seller'])->where('section_category', 'LIKE', '%"exclusive_deal"%')->where('super_status', 'approved')->limit(8)->get();
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
            'variant_option_ids' => 'nullable|array'
        ]);

        $product = products::findOrFail($request->product_id);
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

        $price = $this->calculatePrice($product, $variantOptionIds, $request->variant_option_ids);

        $userId = Auth::id();

        Cart::create([
            'user_id' => $userId,
            'product_id' => $product->id,
            'variant_option_ids' => $variantOptionIds,
            'quantity' => $request->quantity,
            'price' => $price
        ]);

        $cartData = Cart::where('user_id', Auth::user()->id)->get();
        $countCart = $cartData->count();

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
            'cartCount' => $cartItems->count()
        ]);
    }

    public function wishList()
    {
        $wishlistData = Wishlist::with(['product', 'product.images'])->where('user_id', Auth::user()->id)->get();

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

        $wishlistData = Wishlist::where('user_id', Auth::user()->id)->get();
        $wishlistCount = $wishlistData->count();

        Wishlist::create([
            'user_id' => $userId,
            'product_id' => $product->id,
            'variant_option_ids' => $variantOptionIds,
            'quantity' => $request->quantity,
            'price' => $price,
        ]);

        return response()->json(['status' => 'success', 'wishlistCount' => $wishlistCount]);
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

        $wishlistCounter = Wishlist::where('user_id', Auth::user()->id)->get()->count();
        $cartItems = Cart::where('user_id', Auth::id())->get();
        $cartCounter = $cartItems->count();

        $totalPrice = $cartItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        return response()->json(['success' => true, 'id' => $request->id, 'wishlistCount' => $wishlistCounter, 'cartCounter' => $cartCounter, 'totalPrice' => $totalPrice]);
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
        $cartData = Cart::with(['product', 'product.images'])->where('user_id', Auth::user()->id)->get();

        return view('shop.cart', compact('cartData'));
    }

    public function checkout()
    {
        try {

            $cartData = Cart::with('product')->where('user_id', Auth::user()->id)->get();
            $order = orders::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->first();

            $billingAddress = [];
            if ($order) {
                $orderId = $order->id;
                $billingAddress = BillingDetail::where('order_id', $orderId)->get();
            }

            return view('shop.checkout', compact('cartData', 'billingAddress'));
        } catch (Exception $e) {
            return redirect()->back()->with('something wrong!');
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
                'name' => 'required|string',
                'email' => 'required|email',
                'review' => 'required|string',
                'rating' => 'required|integer|min:1|max:5',
            ]
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        Review::create(
            [
                'product_id' => $request->productId,
                'user_id' => Auth::user()->id,
                'name'   => $request->name,
                'email'  => $request->email,
                'review' => $request->review,
                'rating' => $request->rating,
            ]
        );

        return redirect()->back()->with('Product Review is submitted successfully!');
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
        $cartData = Cart::with(['product.seller'])->where('user_id', Auth::user()->id)->get();
        $user = Auth::user();

        if ($couponData) {
            if ($couponData->status == 'active') {
                if (Carbon::now()->between($couponData->starts_at, $couponData->expires_at)) {
                    if ($couponData->used_count <= $couponData->usage_limit) {

                        $assignedUserIds = [];

                        if ($couponData->applicable_to != 'all_users' && $couponData->applicable_to != 'all_products') {

                            $assignedUserIds = $couponData->assignments->where('assignable_type', 'user')->pluck('assignable_id')->toArray();

                            $productIds = $couponData->assignments->pluck('assignable_id')->toArray();
                            $cartProductIds = $cartData->pluck('product_id')->toArray();

                            $matchedProductIds = array_intersect($cartProductIds, $productIds);

                            // working fine for products
                            $matchingCartItems = $cartData->whereIn('product_id', $matchedProductIds);

                            // working fine for user
                            $matchingCartItems = $cartData->whereIn('user_id', Auth::user()->id);
                        } else {
                            $matchingCartItems = $cartData;
                        }

                        $matchedSubtotal = $matchingCartItems->sum(function ($item) {
                            return $item->price * $item->quantity;
                        });

                        $perItemDiscounts = [];
                        $totalDiscount = 0;

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
        dd($request->all());
    }
}
