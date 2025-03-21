<?php

namespace App\Helpers;

use App\Models\Cart;
use App\Models\CartItem;

class CartHelper
{
    public static function calculateSubtotal($cart): float
    {
        $subtotal = 0;

        foreach ($cart->items()->with('product')->get() as $item) {
            $subtotal += $item->quantity * $item->product->price;
        }

        return round($subtotal, 2);
    }


    public static function calculateTax($cart, float $taxRate = 0.20): float
    {
        $subtotal = self::calculateSubtotal($cart);
        return round($subtotal * $taxRate, 2);
    }

    public static function calculateTotal($cart, float $taxRate = 0.20)
    {
        $subtotal = self::calculateSubtotal($cart);
        $tax = self::calculateTax($cart, $taxRate);

        $total = $subtotal + $tax;

        return round($total, 2);
    }
}
