<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Display list of cart items.
     */
    public function index()
    {
        // return user's cart group by product store id
        // $cart = request()->user()->cart()->with('product.store')->get()->groupBy(function ($item) {
        //     return $item->product->store->id;
        // });
        $cart = request()->user()->cart;
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

        // If amount is 0, remove item from cart
        if ($request->amount == 0) {
            $request->user()->cart()->detach($request->product_id);
            return response()->json(['message' => 'Item removed from cart.']);
        }

        // Add item to cart
        $request->user()->cart()->updateOrCreate(
            ['product_id' => $request->product_id],
            ['amount' => $request->amount]
        );

        return response()->json(['message' => 'Item added to cart.']);
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
        // Validate request
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'amount' => 'required|integer|min:0'
        ]);

        // If amount is 0, remove item from cart
        if ($request->amount == 0) {
            $request->user()->cart()->detach($request->product_id);
            return response()->json(['message' => 'Item removed from cart.']);
        }

        // Update cart item
        $request->user()->cart()->updateOrCreate(
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
        // Validate request
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'address' => 'required|string',
        ]);

        // Get cart items for store
        $cart = request()->user()->cart()->with('product.store')->get()->filter(function ($item) use ($request) {
            return $item->product->store->id == $request->store_id;
        });

        // Check if cart is empty
        if ($cart->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        if (!$cart->contains(function ($item) {
            return $item->price_discounted > 0;
        })) {

            // Check if product is in stock
            if (!$cart->contains(function ($item) {
                return $item->product->stock >= $item->amount;
            })) {
                return response()->json(['message' => 'Not enough stock.'], 422);
            }

            // Calculate total product price
            $total = $cart->sum(function ($item) {
                // If there is product sale price, use it
                if ($item->product->price_discounted > 0) {
                    return $item->product->price_discounted * $item->amount;
                }
                return $item->product->price * $item->amount;
            });

            // Create transaction code tied to user, time and random upper case letters
            $code = request()->user()->id . '-' . time() . '-' . Str::upper(Str::random(5));

            // Create order
            $order = Order::create([
                'user_id' => request()->user()->id,
                'store_id' => $cart->first()->product->store->id,
                'total' => $total,
                'transaction_code' => $code,
                'dropoff_location' => $request->address
            ]);

            // Create order details and reduce product stock
            foreach ($cart as $item) {
                $order->orderDetails()->create([
                    'product_id' => $item->product->id,
                    'amount' => $item->amount,
                    'subtotal' => $item->price_discounted ?? $item->product->price_discounted ?? $item->product->price,
                ]);

                // Remove cart item from cart
                $item->delete();
            }

            // Refresh orderDetails
            $order->load('orderDetails');
            return response()->json(['message' => 'Order placed.', 'order' => $order]);
        }


        // Calculate total product price
        $total = $cart->sum(function ($item) {
            // If there is product sale price, use it
            if ($item->price_discounted > 0) {
                return $item->price_discounted * $item->amount;
            }
            if ($item->product->price_discounted > 0) {
                return $item->product->price_discounted * $item->amount;
            }
            return $item->product->price * $item->amount;
        });

        // Create transaction code tied to user, time and random upper case letters
        $code = request()->user()->id . '-' . time() . '-' . Str::upper(Str::random(5));

        // Create order
        $order = Order::create([
            'user_id' => request()->user()->id,
            'store_id' => $cart->first()->product->store->id,
            'total' => $total,
            'transaction_code' => $code,
            'dropoff_location' => $request->address
        ]);

        // Create order details
        foreach ($cart as $item) {
            $order->orderDetails()->create([
                'product_id' => $item->product->id,
                'amount' => $item->amount,
                'subtotal' => $item->price_discounted ?? $item->product->price_discounted ?? $item->product->price,
            ]);
            // Remove cart item from cart
            $item->delete();
        }
        return response()->json(['message' => 'Cart checked out.']);
    }
}
