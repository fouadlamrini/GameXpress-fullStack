<?php

namespace App\Helpers;

use App\Models\Product;
use Exception;

class ProductHelper
{
    public static function hasEnoughStock($product, int $requestedQuantity, bool $throwException = false): bool
    {
        if ($product->stock < $requestedQuantity) {
            return false;
        }

        return true;
    }
}