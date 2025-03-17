<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_dashboard');
    }

    public function index()
    {
        $statistics = [
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'total_users' => User::count(),
            'low_stock_products' => Product::where('stock', '<=', 10)->count(),
            'out_of_stock_products' => Product::where('status', 'out_of_stock')->count(),
            'recent_products' => Product::with(['category'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get(),
            'stock_alerts' => Product::with(['category'])
                ->where('stock', '<=', 10)
                ->where('status', 'available')
                ->orderBy('stock', 'asc')
                ->take(5)
                ->get()
        ];

        return response()->json($statistics);
    }
}
