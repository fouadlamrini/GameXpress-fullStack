<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_products')->only(['index', 'show']);
        $this->middleware('permission:create_products')->only('store');
        $this->middleware('permission:edit_products')->only('update');
        $this->middleware('permission:delete_products')->only('destroy');
    }

    public function index()
    {
        $products = Product::with(['category', 'images'])->paginate(10);
        return response()->json($products);
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);

        if (isset($validated['category_id'])) {
            $validated['sub_category_id'] = null;
        } elseif (isset($validated['sub_category_id'])) {
            $validated['category_id'] = null;
        }

        $product = Product::create($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('productsImages', 'public');

                $product->images()->create([
                    'image_url' => $path,
                    'is_primary' => $index === 0
                ]);
            }
        }

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load('images')
        ], 201);
    }

    public function show(Product $product)
    {
        return response()->json($product->load(['category', 'images']));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['name']);

        if (Product::where('slug', $validated['slug'])->where('id', '!=', $product->id)->exists()) {
            return response()->json(['message' => 'Product already exists.'], 409);
        }

        if (isset($validated['category_id'])) {
            $validated['sub_category_id'] = null;
        } elseif (isset($validated['sub_category_id'])) {
            $validated['category_id'] = null;
        }

        $product->update($validated);

        return response()->json($product->load(['category', 'images']));
    }

    public function destroy(Product $product)
    {
        $product->images()->delete();
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully.'], 200);
    }
}
