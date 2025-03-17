<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_products')->only(['index', 'show']);
        $this->middleware('permission:create_products')->only('store');
        $this->middleware('permission:edit_products')->only('setPrimary');
        $this->middleware('permission:delete_products')->only('destroy');
    }

    public function index(Product $product)
    {
        return response()->json($product->images);
    }

    public function show(Product $product, ProductImage $image)
    {
        return response()->json($image);
    }

    public function store(Request $request, Product $product)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        foreach ($request->file('images') as $imageFile) {
            $path = $imageFile->store('products', 'public');

            $product->images()->create([
                'image_url' => $path,
                'is_primary' => $product->images()->count() === 0 // Make primary if first image
            ]);
        }

        return response()->json([
            'message' => 'Images uploaded successfully',
            'images' => $product->images
        ], 201);
    }

    public function destroy(ProductImage $image)
    {
        if ($image->product->images()->count() === 1) {
            return response()->json([
                'message' => 'Cannot delete the last image of a product'
            ], 400);
        }

        if ($image->is_primary) {
            $newPrimary = $image->product->images()
                ->where('id', '!=', $image->id)
                ->first();
            if ($newPrimary) {
                $newPrimary->update(['is_primary' => true]);
            }
        }

        Storage::disk('public')->delete($image->image_url);
        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully'
        ]);
    }



    public function setPrimary(Product $product, ProductImage $image)
    {
        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return response()->json([
            'message' => 'Primary image updated successfully',
            'image' => $image
        ]);
    }
}
