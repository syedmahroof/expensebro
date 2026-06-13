<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * List the global categories plus the user's own.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $request->user()->id))
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }
}
