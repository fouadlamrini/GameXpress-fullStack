<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Order;
use App\Models\Payment;
use App\Http\Requests\StorePaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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
            'status' => 'pending', // Initial status before processing
            'payment_details' => $validated['payment_details'] ?? null,
        ]);
        
        
        // lahcen o fuad hna stripe integgration
        


        if ($payment->status === 'completed') {
            $order->update(['status' => 'processing']);
        }
        
        return response()->json([
            'message' => 'Payment processed successfully',
            'payment' => $payment->fresh()->load('order')
        ], 201);
    }
}