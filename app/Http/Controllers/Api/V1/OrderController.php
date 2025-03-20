<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store()
    {
        $user = Auth::user();

        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty or does not exist',
            ], 404);
        }

        $cartItems = CartItem::where('cart_id', $cart->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty',
            ], 404);
        }

        $totalPrice = 0;
        foreach ($cartItems as $cartItem) {
            $product = Product::find($cartItem->product_id);
            $totalPrice += $product->price * $cartItem->quantity;
        }

        DB::beginTransaction();

        try {
            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            foreach ($cartItems as $cartItem) {
                $product = Product::find($cartItem->product_id);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $product->price,
                ]);
            }

            CartItem::where('cart_id', $cart->id)->delete();
            $cart->delete();

            DB::commit();

            return $order;

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        $user = Auth::user();
        $orders = Order::where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ], 200);
    }

    public function show($orderId)
    {
        $user = Auth::user();
        $order = Order::with('orderItems.product')->where('user_id', $user->id)->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
        ], 200);
    }

    public function cancel(Order $order)
    {
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled',
            ], 400);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
            'data' => $order,
        ], 200);
    }
}