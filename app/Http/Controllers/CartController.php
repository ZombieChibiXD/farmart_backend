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
        $request->user()->cart()->where('is_checked_out', false)->delete();

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
        ]);

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
        $total = 0;

        if (!$cart_items->contains(function ($item) {
            return $item->price_discounted > 0;
        })) {
            // Check if product is in stock
            if (!$cart_items->contains(function ($item) {
                return $item->product->stock >= $item->amount;
            })) {
                return response()->json(['message' => 'Not enough stock.'], 422);
            }

            // Calculate total product price
            $total = $cart_items->sum(function ($item) {
                // Substraction of product stock and amount and save
                $item->product->stock -= $item->amount;
                $item->product->save();

                // If there is product sale price, use it
                if ($item->product->price_discounted > 0) {
                    $order_details[] = [
                        'product_id' => $item->product_id,
                        'price' => $item->product->price_discounted,
                        'amount' => $item->amount,
                        'subtotal' => $item->product->price_discounted * $item->amount
                    ];
                    return $item->product->price_discounted * $item->amount;
                }
                // Otherwise use product price
                $order_details[] = [
                    'product_id' => $item->product_id,
                    'price' => $item->product->price,
                    'amount' => $item->amount,
                    'subtotal' => $item->product->price * $item->amount
                ];
                return $item->product->price * $item->amount;
            });


        } else {
            // Calculate total product price
            $total = $cart_items->sum(function ($item) {
                // If there is product sale price, use it
                if ($item->price_discounted > 0) {
                    $order_details[] = [
                        'product_id' => $item->product_id,
                        'price' => $item->price_discounted,
                        'amount' => $item->amount,
                        'subtotal' => $item->price_discounted * $item->amount
                    ];
                    return $item->price_discounted * $item->amount;
                }
                if ($item->product->price_discounted > 0) {
                    $order_details[] = [
                        'product_id' => $item->product_id,
                        'price' => $item->product->price_discounted,
                        'amount' => $item->amount,
                        'subtotal' => $item->product->price_discounted * $item->amount
                    ];
                    return $item->product->price_discounted * $item->amount;
                }
                // Otherwise use product price
                $order_details[] = [
                    'product_id' => $item->product_id,
                    'price' => $item->product->price,
                    'amount' => $item->amount,
                    'subtotal' => $item->product->price * $item->amount
                ];
                return $item->product->price * $item->amount;
            });
        }



        // Create transaction code tied to user, time and random upper case letters
        $code = request()->user()->id . '-' . time() . '-' . Str::upper(Str::random(5));


        // Create order
        $order = Order::create([
            'user_id' => $user->id,
            'store_id' => $request->store_id,
            'total' => $total,
            'transaction_code' => $code,
            'dropoff_location' => $request->address
        ]);

        // If order is not created, return error
        if (!$order) {
            // Rollback product stock
            $cart_items->each(function ($item) {
                $item->product->stock += $item->amount;
                $item->product->save();
            });
            return response()->json(['message' => 'Order could not be created.'], 422);
        }

        // Attach order details to order
        $order->orderDetails()->createMany($order_details);

        // Remove cart items from user
        foreach ($cart_items as $cart_item) {
            $user->cart()->detach($cart_item->id);
        }


        // Refresh orderDetails
        $order->load('orderDetails');
        return response()->json(['message' => 'Order placed.', 'order' => $order]);
    }
}
