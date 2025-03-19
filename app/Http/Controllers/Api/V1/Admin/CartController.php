<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class CartController extends Controller
{
    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'session_id' => 'required_without:user_id'
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->stock < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock'
            ], 400);
        }

        $cart = $this->getCart($request->session_id);

        $cartItem = $this->addToCart($cart, $product, $validated['quantity']);

        return response()->json([
            'message' => 'Item added to cart',
            'cart_item' => $cartItem
        ], 201);
    }

    public function updateItem(Request $request, CartItem $cartItem)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        if ($cartItem->product->stock < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock'
            ], 400);
        }
        $cart = $this->getCart($request->session_id);

        $this->updateCartItem($cartItem, $validated['quantity']);

        return response()->json([
            'message' => 'Cart item updated',
            'cart_item' => $cartItem
        ]);
    }

    private function updateCartItem($item, $quantity)
    {
        $item->quantity = $quantity;
        $item->save();
        return $item->fresh();
    }

    private function addToCart($cart, $product, $quantity)
    {
        $existingItem = $cart->items()->where('product_id', $product->id)->first();
        $newQuantity = $existingItem ? $existingItem->quantity + $quantity : $quantity;

        $cartItem = CartItem::updateOrCreate(
            [
                'cart_id' => $cart->id,
                'product_id' => $product->id
            ],
            ['quantity' => $newQuantity]
        );
        return $cartItem->load('product');
    }

    public function removeItem(CartItem $cartItem)
    {
        $cartItem->delete();
        return response()->json([
            'message' => 'Item removed from cart'
        ]);
    }

    public function cart(Request $request)
    {
        $cart = $this->getCart($request->session_id);
        return response()->json([
            'cart' => $cart,
            'items' => $cart->items
        ]);
    }

    private function getCart($sessionId): Cart
    {
        if (Auth::check()) {
            return Cart::firstOrCreate([
                'user_id' => Auth::id()
            ]);
        }

        return Cart::firstOrCreate([
            'session_id' => $sessionId
        ]);
    }
}
