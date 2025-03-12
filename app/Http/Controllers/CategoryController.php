<?php

namespace App\Http\Controllers;


use App\Http\Requests;
use App\Models\Category;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CategoryController extends Controller
{
    // this for API
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'data' => $categories,
        ]);
    }
    public function test()
    {
        $categories = Category::all();

        return response()->json([
            'data' => $categories,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $fields = $request->validate(
            [
                'name' => "required",
                'slug' => "required",
            ]
        );

        $category = Category::create($fields);

        return response()->json([
            'data' => $category,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return response()->json([
            'data' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $fields = $request->validate(
            [
                'name' => 'required',
                'slug' => 'required',
            ]
        );

        $category->update($fields);

        return response()->json([
            'data' => $category,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'message' => "Category {$category->name} deleted successfully",
        ]);

    }
}
