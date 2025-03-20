<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Jobs\DeleteProductJob;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use app\Helpers\CartHelper;
use app\Helpers\ProductHelper;

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

        if (!ProductHelper::hasEnoughStock($product, $validated['quantity'])) {
            return response()->json([
                'message' => 'Insufficient stock'
            ], 400);
        }

        $cart = $this->getCart($request->session_id);

        $cartItem = $this->addToCart($cart, $product, $validated['quantity']);

        $totals = CartHelper::calculateTotal($cart);
        return response()->json([
            'message' => 'Item added to cart',
            'cart_item' => $cartItem,
            'totals' => $totals
        ], 201);
    }

    public function updateItem(Request $request, CartItem $cartItem)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        if (!ProductHelper::hasEnoughStock($cartItem->product, $validated['quantity'])) {
            return response()->json([
                'message' => 'Insufficient stock'
            ], 400);
        }
        $cart = $this->getCart($request->session_id);

        $cartItem = $this->addToCart($cart, $cartItem->product, $validated['quantity']);
        $totals = CartHelper::calculateTotal($cart);

        $this->updateCartItem($cartItem, $validated['quantity']);

        return response()->json([
            'message' => 'Cart item updated',
            'cart_item' => $cartItem,
            'totals' => $totals
        ]);
    }

    private function updateCartItem($item, $quantity)
    {
        $item->quantity = $quantity;
        $item->save();

        $cart = $item->cart;
        $totals = CartHelper::calculateTotal($cart);

        return [
            'updated_item' => $item->fresh(),
            'totals' => $totals
        ];
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

        $cart = $cart->fresh();
        $totals = CartHelper::calculateTotal($cart);
        //  return $cartItem->load('product');


        DeleteProductJob::dispatch($cartItem->id)->delay(Carbon::now()->addHours(48));
        return [
            'cart_item' => $cartItem->load('product'),
            'totals' => $totals
        ];
    }

    public function removeItem(CartItem $cartItem)
    {
        $cartItemId = $cartItem->id;

        $cartItem->delete();
        $cart = $cartItem->cart;
        $totals = CartHelper::calculateTotal($cart);

        return response()->json([
            'message' => 'Item removed from cart',
            'totals' => $totals
        ]);
    }

    public function cart(Request $request)
    {
        $cart = $this->getCart($request->session_id);

        $totals = CartHelper::calculateTotal($cart);
        return response()->json([
            'cart' => $cart,
            'items' => $cart->items,
            'totals' => $totals
        ]);
    }

    private function getCart($sessionId): Cart
    {
        if (Auth::check()) {
            $userCart = Cart::firstOrCreate([
                'user_id' => Auth::id()
            ]);

            $cartSession = Cart::where('session_id', $sessionId)->first();
            if ($cartSession) {
                $items = $cartSession->items;
                foreach ($items as $item) {
                    $itemExists = $userCart::where('product_id', $item->product_id)->first();

                    if ($itemExists) {
                        $newQuantity = $itemExists->quantity + $item->quantity;
                        if (ProductHelper::hasEnoughStock($item->product, $newQuantity)) {
                            $itemExists->quantity = $newQuantity;
                        } else {
                            $itemExists->quantity = $item->product->stock;
                        }
                        $itemExists->save();
                        $item->delete();
                    } else {
                        $item->cart_id = $userCart->id;
                        $item->save();
                    }
                }
            }

            return $userCart;
        }

        return Cart::firstOrCreate([
            'session_id' => $sessionId
        ]);
    }
}
