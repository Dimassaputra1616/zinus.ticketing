<?php

namespace App\Http\Controllers;

use App\Models\Tutorial;
use App\Models\Category;
use Illuminate\Http\Request;

class TutorialController extends Controller
{
    public function index(Request $request)
    {
        $query = Tutorial::where('is_active', true);

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category_id', $request->get('category'));
        }

        $tutorials = $query->with('category')->latest()->paginate(12);
        $categories = Category::all();

        return view('tutorials.index', compact('tutorials', 'categories'));
    }

    public function show($slug)
    {
        $tutorial = Tutorial::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $tutorial->increment('views');

        return view('tutorials.show', compact('tutorial'));
    }
}
