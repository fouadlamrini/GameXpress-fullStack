<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HomeController extends Controller
{
    // for api
    public function index()
    {
        $usersCount = User::count();
        $productsCount = Product::count();
        $categoriesCount = Category::count();

        return response()->json([
            'users_count' => $usersCount,
            'products_count' => $productsCount,
            'categories_count' => $categoriesCount,
        ]);
    }
}
