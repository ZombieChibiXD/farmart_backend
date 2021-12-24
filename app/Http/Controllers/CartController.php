<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Promo;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Display list of cart items.
     */
    public function index()
    {
        $cart = request()->user()->cart;

        // Create an array that groups cart items based on their storeIds
        $cartItems = $cart->groupBy('product.store_id');

        // setHidden store in cartItems
        $cartItems->each(function ($group, $key) {
            // For each item in group
            foreach ($group as $value) {
                $value->product->setHidden(['store', 'images']);
            }
        });



        // Get stores from cartItems keys
        $stores = Store::whereIn('id', $cartItems->keys())->get();

        // Return as JSON response
        return response()->json(['stores' => $stores, 'cart' => $cartItems]);
    }

    public function negotiate(Request $request)
    {
        // Get fields and validate user_id, product_id, amount, price_discounted
        $fields = $request->validate([
            'user_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'amount' => 'required|integer|min:1',
            'price_discounted' => 'numeric|min:0'
        ]);

        // delete cart item with user_id, product_id
        Cart::where('user_id', $fields['user_id'])
            ->where('product_id', $fields['product_id'])
            ->delete();

        // Create new cart item
        $cart = Cart::create($fields);

        // Return as JSON response
        return response()->json($cart);
    }

    /**
     * Add item to cart.
     */
    public function store(Request $request)
    {
        // Validate request
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'amount' => 'required|integer|min:0'
        ]);
        $user = request()->user();

        // If amount is 0, remove item from cart
        if ($request->amount == 0) {
            // Get item from cart
            $item = $user->cart->where('product_id', $request->product_id)->first();
            if ($item) {
                // Remove item from cart
                $item->delete();
                return response()->json(['message' => 'Item removed from cart.']);
            } else {
                return response()->json(['message' => 'Item not found in cart.'], 404);
            }
        }
        // Find or add item to cart
        $item = $user->cart->where('product_id', $request->product_id)->first();
        if ($item) {
            // Update item amount
            $result = $item->update(['amount' => $request->amount]);
            if ($result) return response()->json(['message' => 'Cart item updated!']);
            else return response()->json(['message' => 'Cart item update failed'], 500);
        } else {
            // Add item to cart
            $result = $user->cart()->updateOrCreate(
                ['product_id' => $request->product_id],
                ['amount' => $request->amount]
            );
            if ($result) return response()->json(['message' => 'Item added to cart!']);
            else return response()->json(['message' => 'Cart item addition failed'], 500);
        }
        return response()->json(['message' => 'Unknown server error.'], 500);
    }

    /**
     * Remove item from cart.
     */
    public function remove(Request $request)
    {
        // Validate request
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        // Remove item from cart
        $request->user()->cart()->detach($request->product_id);

        return response()->json(['message' => 'Item removed from cart.']);
    }

    /**
     * Clear cart.
     */
    public function clear(Request $request)
    {
        // Clear cart
        $request->user()->cart()->delete();

        return response()->json(['message' => 'Cart cleared.']);
    }

    /**
     * Update cart item.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        // Validate request, product_id is required and exists in products table
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'amount' => 'required|integer|min:0'
        ]);

        // Check if product is in cart
        $cart_item = $user->cart()->where('product_id', $request->product_id)->first();

        // If product is not in cart, return error
        if (!$cart_item) {
            return response()->json(['message' => 'Item not in cart.'], 404);
        }


        // If amount is 0, remove item from cart
        if ($request->amount == 0) {
            $request->user()->cart()->detach($request->product_id);
            return response()->json(['message' => 'Item removed from cart.']);
        }

        // Update cart item, don't create new cart item
        $user->cart()->update(
            ['product_id' => $request->product_id],
            ['amount' => $request->amount]
        );

        return response()->json(['message' => 'Item updated in cart.']);
    }

    /**
     * Checkout cart.
     */
    public function checkout(Request $request)
    {
        $user = request()->user();
        // Validate request
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'address' => 'required|string',
            'promo_code' => 'nullable|string|exists:promos,code',
        ]);
        $PROMO_PERCENTAGE = 1;
        $PROMO_FIXED = 2;

        $promo = null;
        $promo_type = 0;
        $promo_amount = null;
        $promo_seasons = [];
        // If promo code is provided, check if it is valid
        if ($request->promo_code) {
            // Find promo code with code from request and check if it's usable
            $promo = Promo::where('code', $request->promo_code)->where('usable', true)->first();
            if (!$promo) {
                return response()->json(['message' => 'Promo code is invalid.'], 422);
            }
            if($promo->season){
                $promo_seasons = explode(',', $promo->season);
                // Trim $promo_seasons
                $promo_seasons = array_map('trim', $promo_seasons);
                // To lowercase
                $promo_seasons = array_map('strtolower', $promo_seasons);

            }

            $promo_amount = strpos($promo->value, '%') !== false ?
                (((float)str_replace('%', '', $promo->value)) / 100) :
                (float)$promo->value;
            $promo_type = strpos($promo->value, '%') !== false ? $PROMO_PERCENTAGE : $PROMO_FIXED;
        }

        // Get cart item with product table where store_id is equal to store_id from request
        $cart_items = $user->cart()->whereHas('product', function ($query) use ($request) {
            $query->where('store_id', $request->store_id);
        })->get();


        // Check if cart items are empty
        if ($cart_items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 404);
        }

        // Order details array
        $order_details = [];

        $store_discount = $cart_items->contains(function ($item) {
            return $item->price_discounted > 0;
        });


        if (!$store_discount && !$cart_items->contains(function ($item) {
            return $item->product->stock >= $item->amount;
        })) {
            return response()->json(['message' => 'Not enough stock.'], 422);
        }

        // For each cart item
        foreach ($cart_items as $item) {

            if(!$store_discount){
                // Subtraction of product stock and amount and
                // update product stock
                $item->product->stock -= $item->amount;
                $item->product->save();
            }

            // If there is product sale price, use it
            $product_price = $item->price_discounted ?? $item->product->price_discounted ?? $item->product->price;

            $product_in_season = false;
            // Check if promo_seasons is more than 0
            if(count($promo_seasons) > 0 && $item->product->seasons){
                $item_seasons_string = $item->product->seasons;

                // Explode and trim item_seasons_string
                $item_seasons = explode(',', $item_seasons_string);
                $item_seasons = array_map('trim', $item_seasons);
                // To lowercase
                $item_seasons = array_map('strtolower', $item_seasons);

                // Check if item_seasons is in promo_seasons
                $product_in_season = count(array_intersect($item_seasons, $promo_seasons)) > 0;
            }

            if($product_in_season) {
                switch($promo_type){
                    case $PROMO_PERCENTAGE:
                        $product_price = $product_price - ($product_price * $promo_amount);
                        break;
                    case $PROMO_FIXED:
                        $product_price = $product_price - $promo_amount;
                        break;
                }

                if($product_price < 0){
                    $product_price = 0;
                }
            }
            $order_details[] = [
                'product_id' => $item->product_id,
                'price' => $product_price,
                'amount' => $item->amount,
                'subtotal' => $product_price * $item->amount
            ];
        }
        $total = array_sum(array_column($order_details, 'subtotal'));

        $discount = 0;
        if($promo == [] || $promo == null){
        }else{
            switch($promo_type){
                case $PROMO_PERCENTAGE:
                    $discount = $total * $promo_amount;
                    break;
                case $PROMO_FIXED:
                    $discount = $promo_amount;
                    break;
            }
        }
        $promo_value = '';

        if($promo){
            $promo_value = $promo->type??'' . ' ' . $promo->value??'';
        }


        // Create order
        $order = Order::create([
            'user_id' => $user->id,
            'store_id' => $request->store_id,
            'total' => $total - $discount,
            'transaction_code' => '',
            'dropoff_location' => $request->address,
            'promo_value' => $promo_value,
        ]);

        // If order is not created, return error
        if (!$order) {
            if (!$store_discount) {
                foreach ($cart_items as $item) {
                    $item->product->stock += $item->amount;
                    $item->product->save();
                }
            }
            return response()->json(['message' => 'Order could not be created.'], 422);
        }
        $order->transaction_code = OrderController::generateTransactionCode($user->id, $order->created_at->timestamp);

        // If transaction code save failed
        if (!$order->save()) {
            $order->delete();
            return response()->json(['message' => 'Order could not be created.'], 422);
        }

        // Attach order details to order
        $order->orderDetails()->createMany($order_details);

        // Remove cart items from user
        foreach ($cart_items as $cart_item) {
            $cart_item->delete();
        }


        // Refresh orderDetails
        $order->load('orderDetails');

        // Hide product store from order details
        $order->orderDetails->each(function ($orderDetail) {
            $orderDetail->product->setHidden(['store', 'images']);
        });
        return response()->json(['message' => 'Order placed.', 'order' => $order]);
    }
}
