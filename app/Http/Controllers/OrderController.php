<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public static function generateTransactionCode(int $user_id, int $timestamp)
    {
        $timeStr = Str::upper(Str::padLeft(base_convert($timestamp, 10, 36), 8, '0'));
        $uidStr = Str::upper(Str::padLeft(base_convert($user_id, 10, 36), 5, '0'));
        $randomStr = Str::upper(Str::random(5));


        // Create transaction code tied to user, time and random upper case letters
        return $randomStr . '-' . $timeStr . '-' . $uidStr;
    }
    /**
     * Display list of cart items.
     */
    public function index()
    {
        // Get user orders sorted by status and created_at desc
        $orders = request()->user()->orders()->orderBy('status', 'desc')->orderBy('created_at', 'desc')->get()->groupBy('status');

        // Hide product store in order detail within order
        $orders->each(function ($group) {
            $group->each(function ($order) {
                $order->orderDetails->each(function ($detail) {
                    $detail->product->setHidden(['store', 'images', 'price', 'price_discounted', 'slug']);
                });
            });
        });

        // Return as JSON response
        return response()->json(['statuses'=>Order::statusText(),'orders'=> $orders]);
    }


    /**
     * Display list of cart items.
     */
    public function store_list()
    {
        // Get store_id from route
        $store_id = request()->route('store_id');

        // Get orders by store_id and sorted by status and created_at desc and not cancelled or not pending payment group by status
        $orders = Order::where('store_id', $store_id)->orderBy('status', 'desc')->orderBy('created_at', 'desc')->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_PENDING_PAYMENT])->get()->groupBy('status');

        // Hide product store in order detail within order
        $orders->each(function ($group) {
            $group->each(function ($order) {
                $order->orderDetails->each(function ($detail) {
                    $detail->product->setHidden(['store', 'images', 'price', 'price_discounted', 'slug']);
                });
            });
        });

        // Return as JSON response
        return response()->json(['statuses'=>Order::statusText(),'orders'=> $orders]);
    }

    /**
     * Display list of cart items.
     */
    public function admin_list()
    {
        // Get orders sorted by status and created_at desc and pending payment or shipped
        $orders = Order::where('status', '=', Order::STATUS_PENDING_PAYMENT)
            ->orWhere('status', '=', Order::STATUS_SHIPPED)->orderBy('status', 'desc')->orderBy('created_at', 'desc')->get()->groupBy('status');

        // Hide product store in order detail within order
        $orders->each(function ($group) {
            $group->each(function ($order) {
                $order->orderDetails->each(function ($detail) {
                    $detail->product->setHidden(['store', 'images', 'price', 'price_discounted', 'slug']);
                });
            });
        });

        // Return as JSON response
        return response()->json(['statuses'=>Order::statusText(),'orders'=> $orders]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate request product id and amount
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'amount' => 'required|integer|min:1',
            'address' => 'required|string',
        ]);

        $user = request()->user();

        // Get product
        $product = Product::find($request->product_id);

        // Update total
        $order = Order::create([
            'user_id' => $user->id,
            'store_id' => $product->store->id,
            'total' => ($product->price_discounted ?? $product->price) * $request->amount,
            'dropoff_location' => $request->address,
            'transaction_code' => ''
        ]);

        if (!$order) {
            return response()->json(['message' => 'Order failed.'], 500);
        }

        $order->transaction_code = self::generateTransactionCode($user->id, $order->created_at->timestamp);

        if (!$order->save()) {
            return response()->json(['message' => 'Order failed.'], 500);
        }


        $orderDetail = null;

        // Add order detail to order if price discount is more than 0
        if ($product->price_discounted > 0) {
            $orderDetail = $order->orderDetails()->create([
                'product_id' => $product->id,
                'amount' => $request->amount,
                'subtotal' => $product->price_discounted * $request->amount
            ]);
        } else {
            $orderDetail = $order->orderDetails()->create([
                'product_id' => $product->id,
                'amount' => $request->amount,
                'subtotal' => $product->price * $request->amount
            ]);
        }

        // Update product stock if order detail is successfully created
        if ($orderDetail) {
            $product->stock -= $request->amount;
            $product->save();
        } else {
            $order->delete();
            // Return error if order detail is not created
            return response()->json(['message' => 'Order detail not created.'], 500);
        }

        // Return as JSON response
        return response()->json(['message' => 'Order placed.', 'order' => $order]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        // Get order id from route
        $order_id = $request->route('order_id');

        $user = request()->user();

        // Find order by order_id and user_id
        $order = $user->orders()->find($order_id);

        if (!$order) {
            return response()->json(['message' => 'Order is not found!'], 404);
        }

        // Hide product store in order detail within order
        $order->orderDetails->each(function ($detail) {
            $detail->product->setHidden(['store', 'images', 'price', 'price_discounted', 'slug']);
        });

        // Return as JSON response
        return response()->json($order);
    }

    /**
     * Update order as paid
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function pay(Request $request)
    {
        // Validate request
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        // Get order
        $order = Order::find($request->order_id);

        // If order is not pending payment
        if ($order->status != Order::STATUS_PENDING_PAYMENT) {
            return response()->json(['message' => 'Order is not pending payment.'], 400);
        }

        // Update order status to paid
        $order->update(['status' => Order::STATUS_PAID]);

        // Return as JSON response
        return response()->json(['message' => 'Order paid.']);
    }

    /**
     * Update order as delivered
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deliver(Request $request)
    {
        // Validate request
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        // Get order
        $order = Order::find($request->order_id);

        // If order is not shipped, return error
        if ($order->status != Order::STATUS_SHIPPED) {
            return response()->json(['message' => 'Order is not shipped.'], 500);
        }

        // Update order status to delivered
        $order->update(['status' => Order::STATUS_DELIVERED]);

        // Update product sold
        $order->orderDetails->each(function ($detail) {
            $detail->product->sold += $detail->amount;
            $detail->product->save();
        });
        // Create a new notification with text Hello world from order user
        $order->user->notifications->create([
            'type' => 'Order delivery',
            'data' => 'Your order '.$order->transaction_code .' been delivered.'
        ]);

        // Return as JSON response
        return response()->json(['message' => 'Order delivered.']);
    }

    /**
     * Update order as cancelled
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function cancel(Request $request)
    {

        // Validate request
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $user = request()->user();

        // Get order
        $order = Order::find($request->order_id);

        //  Check if user is the owner of the order
        if ($order->user_id != $user->id) {
            return response()->json(['message' => 'You are not authorized to cancel this order.'], 403);
        }

        $no_cancel_status = [
            Order::STATUS_PAID,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED
        ];

        // If Order status is within no cancel status then return error
        if (in_array($order->status, $no_cancel_status)) {
            return response()->json(['message' => 'You cannot cancel this order.'], 403);
        }

        // Update order status to cancelled
        $order->update(['status' => Order::STATUS_CANCELLED]);

        // Return as JSON response
        return response()->json(['message' => 'Order cancelled.']);
    }

    /**
     * Update order as cancelled
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function cancel_seller(Request $request)
    {

        // Validate request
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $user = request()->user();

        // Get order
        $order = Order::find($request->order_id);

        //  Check if user manages the store
        if (!$user->managesStore($order->store_id)) {
            return response()->json(['message' => 'You are not authorized to cancel this order.'], 403);
        }

        $no_cancel_status = [
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED
        ];

        // If Order status is within no cancel status then return error
        if (in_array($order->status, $no_cancel_status)) {
            return response()->json(['message' => 'You cannot cancel this order.'], 403);
        }

        // Update order status to cancelled
        $order->update(['status' => Order::STATUS_CANCELLED]);

        // Return as JSON response
        return response()->json(['message' => 'Order cancelled.']);
    }

    /**
     * Update order as shipped
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function
    ship(Request $request)
    {
        // Validate request
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'courier_code' => 'required|string'
        ]);
        $user = request()->user();

        // Get order
        $order = Order::find($request->order_id);

        if (!$user->managesStore($order->store_id)) {
            return response()->json(['message' => 'You are not authorized to ship this order.'], 403);
        }

        // If order status is not paid then return error
        if ($order->status != Order::STATUS_PAID) {
            return response()->json(['message' => 'You cannot ship this order.'], 403);
        }

        // Update order status to shipped and courier code
        $order->update([
            'status' => Order::STATUS_SHIPPED,
            'courier_code' => $request->courier_code,
        ]);

        // Return as JSON response
        return response()->json(['message' => 'Order shipped.']);
    }
}
