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

        // Add item to cart
        $user->cart()->updateOrCreate(
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
        $store_discount = $cart_items->contains(function ($item) {
            return $item->price_discounted > 0;
        });

        if (!$store_discount) {
            // Check if product is in stock
            if (!$cart_items->contains(function ($item) {
                return $item->product->stock >= $item->amount;
            })) {
                return response()->json(['message' => 'Not enough stock.'], 422);
            }

            // For each cart item
            foreach ($cart_items as $item) {

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
                    $total += $item->product->price_discounted * $item->amount;
                }
                // Otherwise use product price
                else {
                    $order_details[] = [
                        'product_id' => $item->product_id,
                        'price' => $item->product->price,
                        'amount' => $item->amount,
                        'subtotal' => $item->product->price * $item->amount
                    ];
                    $total += $item->product->price * $item->amount;
                }
            }
        } else {


            // For each cart item
            foreach ($cart_items as $item) {
                // If there is product sale price, use it

                if ($item->price_discounted > 0) {
                    $order_details[] = [
                        'product_id' => $item->product_id,
                        'price' => $item->price_discounted,
                        'amount' => $item->amount,
                        'subtotal' => $item->price_discounted * $item->amount
                    ];
                    $total += $item->price_discounted * $item->amount;
                } else if ($item->product->price_discounted > 0) {
                    $order_details[] = [
                        'product_id' => $item->product_id,
                        'price' => $item->product->price_discounted,
                        'amount' => $item->amount,
                        'subtotal' => $item->product->price_discounted * $item->amount
                    ];
                    $total += $item->product->price_discounted * $item->amount;
                } else {
                    $order_details[] = [
                        'product_id' => $item->product_id,
                        'price' => $item->product->price,
                        'amount' => $item->amount,
                        'subtotal' => $item->product->price * $item->amount
                    ];
                    $total += $item->product->price * $item->amount;
                }
            }
        }

        $timeStr = Str::upper(Str::padLeft(base_convert(time(), 10, 36), 8, '0'));
        $uidStr = Str::upper(Str::padLeft(base_convert($user->id, 5, 36), 5, '0'));
        $randomStr = Str::upper(Str::random(5));


        // Create transaction code tied to user, time and random upper case letters
        $code = $randomStr . '-' . $timeStr . '-' . $uidStr;

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
            if (!$store_discount) {
                foreach ($cart_items as $item) {
                    $item->product->stock += $item->amount;
                    $item->product->save();
                }
            }
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
