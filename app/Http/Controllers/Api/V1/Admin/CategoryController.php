<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_categories')->only(['index', 'show']);
        $this->middleware('permission:create_categories')->only('store');
        $this->middleware('permission:edit_categories')->only('update');
        $this->middleware('permission:delete_categories')->only('destroy');
    }

    public function index()
    {

        $categories = Category::with('subcategories')->paginate(10);
        return response()->json($categories);
    }

    public function indexSubcategory(Category $category)
    {
        $subcategories = $category->subcategories()->paginate(10);
        return response()->json($subcategories);
    }
    
    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);

        // check if slug is unique
        if (Category::where('slug', $validated['slug'])->exists()) {
            return response()->json(['message' => 'Category already exists.'], 409);
        }

        if (!empty($validated['parent_id'])) {
            if (SubCategory::where('slug', $validated['slug'])->exists()) {
                return response()->json(['message' => 'Subcategory already exists.'], 409);
            }
            SubCategory::create($validated);
            return response()->json($validated, 201);
        }
        $category = Category::create($validated);
        return response()->json($category, 201);
    }

    public function show(Category $category)
    {
        return response()->json($category->load('subcategories'));
    }

    public function showSubcategory(Category $category, SubCategory $subcategory)
    {
        return response()->json($subcategory->load('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);
        
        if (Category::where('slug', $validated['slug'])->where('id', '<>', $category->id)->exists()) {
            return response()->json(['message' => 'Category already exists.'], 409);
        }

        $category->update($validated);
        return response()->json($category);
    }
    
    public function updateSubcategory(UpdateCategoryRequest $request, Category $category, SubCategory $subcategory)
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);
        
        if (SubCategory::where('slug', $validated['slug'])->where('id', '<>', $subcategory->id)->exists()) {
            return response()->json(['message' => 'Subcategory already exists.'], 409);
        }
        
        $subcategory->update($validated);
        return response()->json($subcategory);
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return response()->json(['message' => 'Cannot delete category with products.'], 400);
        }
        if ($category->subcategories()->exists()) {
            return response()->json(['message' => 'Cannot delete category with subcategories.'], 400);
        }
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully.'], 200);
    }

    public function destroySubcategory(Category $category, SubCategory $subcategory)
    {
        $subcategories = SubCategory::where('category_id', $category->id)->whereDoesntHave('products')->get();
        if ($subcategory->products()->exists()) {
            return response()->json(['message' => 'Cannot delete subcategory with products.'], 400);
        }
        $subcategory->delete();
        return response()->json(['message' => 'Subcategory deleted successfully.'], 200);
    }
}
