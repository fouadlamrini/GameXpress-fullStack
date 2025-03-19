<?php

namespace App\Helpers;

use App\Models\Product;
use Exception;

class ProductHelper
{
    public static function hasEnoughStock($product, int $requestedQuantity, bool $throwException = false): bool
    {
        if (is_numeric($product)) {
            $product = Product::findOrFail($product);
        }

        if ($product->stock < $requestedQuantity) {
            return false;
        }

        return true;
    }
}