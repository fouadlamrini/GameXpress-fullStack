<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\CartHelper;
use App\Http\Controllers\Api\V1\OrderController;
use App\Models\Order;
use App\Models\Payment;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Cart;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;
use Stripe\Payout;
use Stripe\Stripe;

class PaymentController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:view_payments')->only(['index', 'show']);
        $this->middleware('permission:create_payments')->only('store');
    }


    public function index(Request $request)
    {
        $query = Payment::with(['order', 'order.user']);

        $payments = $query->latest()->paginate(10);

        return response()->json($payments);
    }


    public function show(Payment $payment)
    {
        return response()->json(
            $payment->load(['order', 'order.user', 'order.items.product'])
        );
    }

    // Stripe::setApiKey('sk_test_51R4heoQS9M03ssU9kkb36gp9cMnINZEUjd8ocjvMxKzRqCaMubJzOo6x3BBKf3mrHVbZeNniLuuxBB3CLklDyI2Q00Mg6ZWPZj');
    // $stripe = new \Stripe\StripeClient('sk_test_51R4heoQS9M03ssU9kkb36gp9cMnINZEUjd8ocjvMxKzRqCaMubJzOo6x3BBKf3mrHVbZeNniLuuxBB3CLklDyI2Q00Mg6ZWPZj');
    // $cart_id = Auth::user()->cart->id;
    // $cartItems = CartItem::where('cart_id', $request->cart_id)->get();
    // $cartItems = CartItem::where('cart_id', $cart_id)->get();
    // dd(env('STRIPE_SECRET_KEY'));
    // $session = Session::create([

    public static function charge()
    {
        $order = OrderController::store(Auth::user()->cart);

        if (!$order)
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);

        $orderItems = $order->items;
        $lineItems = [];

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
        foreach ($orderItems as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->product->name,
                    ],
                    'unit_amount' => $item->product->price * 100,
                ],
                'quantity' => $item->quantity,
            ];
        }

        $totalPrice = CartHelper::calculateTotal(Auth::user()->cart);
        $payment  = self::store($order);

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.cancel'),
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'payment_id' => $payment->id
            ]
        ]);
        $payment->payment_details = $session;
        $payment->save();

        return response()->json([
            'message' => 'Payment processed successfully',
            'session_id' => $session->id,
            'url' => $session->url
        ]);
    }

    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        if (!$sessionId) {
            return response()->json([
                'message' => 'Session ID is required'
            ], 400);
        }



        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
        $session = $stripe->checkout->sessions->retrieve($sessionId);
        $payment = Payment::find($session->metadata->payment_id);

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found'
            ], 404);
        }
        $order = $payment->order;

        if (!$order) {
            return response()->json([
                'message' => 'No pending order found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $payment->status = "completed";
            $payment->transaction_id = $session->payment_intent ?? $sessionId;
            $payment->save();

            $order->status = "processing";
            $order->save();

            foreach ($order->items as $item) {
                $product = $item->product;
                $product->stock -= $item->quantity;
                $product->save();
            }
            DB::commit();

            return response()->json([
                'message' => 'Payment completed successfully',
                'order' => $order->fresh()->load('items')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Checkout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public static function store(Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Can only process payment for pending orders'
            ], 422);
        }

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => 'stripe',
            'amount' => $order->total_price,
            'status' => 'pending',
            // 'payment_details' => $details,
        ]);

        if ($payment->status === 'completed') {
            $order->update(['status' => 'processing']);
        }

        return $payment;
    }
}
