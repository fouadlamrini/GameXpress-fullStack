<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\CartHelper;
use App\Helpers\ProductHelper;
use App\Models\Order;
use App\Models\Payment;
use App\Http\Requests\StorePaymentRequest;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Stripe\Stripe;
use Stripe\Checkout\Session;

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


    public function charge(Request $request)
    {
        // $stripe = Stripe::setApiKey(config('services.stripe.secret'));
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
        $cart_id = Auth::user()->cart->id;
        $cartItems = CartItem::where('cart_id', $request->cart_id)->get();
        $cartItems = CartItem::where('cart_id', $cart_id)->get();
        foreach ($cartItems as $item) {
            $totalPrice = CartHelper::calculateTotal($cartItems);
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->product->name,
                    ],
                    'unit_amount' => $item->product->price,
                ],
                'quantity' => $item->quantity,
            ];
        }
        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => 'https://yourwebsite.com/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'https://yourwebsite.com/cancel',
        ]);

        foreach ($cartItems as $item) {
            // $total += $item->product->price * $item->quantity;
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
    }


    public function store(StorePaymentRequest $request)
    {
        $validated = $request->validated();

        $order = Order::findOrFail($validated['order_id']);

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Can only process payment for pending orders'
            ], 422);
        }

        $payment = Payment::create([
            'order_id' => $validated['order_id'],
            'payment_type' => $validated['payment_type'],
            'amount' => $order->total_price,
            'status' => 'pending',
            'payment_details' => $validated['payment_details'] ?? null,
        ]);


        // lahcen o fuad hna stripe integration


        if ($payment->status === 'completed') {
            $order->update(['status' => 'processing']);
        }

        return response()->json([
            'message' => 'Payment processed successfully',
            'payment' => $payment->fresh()->load('order')
        ], 201);
    }
}
