<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TutorialController extends Controller
{
    public function index()
    {
        $tutorials = Tutorial::with(['category', 'author'])->latest()->paginate(10);
        return view('admin.tutorials.index', compact('tutorials'));
    }

    public function create()
    {
        $categories = Category::all();
        $tutorial = new Tutorial(); // For form binding
        return view('admin.tutorials.form', compact('categories', 'tutorial'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'content' => 'required|string',
            'image' => 'nullable|image|max:102400', // max 100MB
        ]);

        $tutorial = new Tutorial($validated);
        $tutorial->user_id = auth()->id();
        $tutorial->slug = Str::slug($validated['title']) . '-' . Str::random(5);
        $tutorial->is_active = $request->has('is_active');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('tutorials', 'public');
            $tutorial->image_path = $path;
        }

        $tutorial->save();

        return redirect()->route('admin.tutorials.index')->with('success', 'Tutorial berhasil ditambahkan.');
    }

    public function edit(Tutorial $tutorial)
    {
        $categories = Category::all();
        return view('admin.tutorials.form', compact('categories', 'tutorial'));
    }

    public function update(Request $request, Tutorial $tutorial)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'content' => 'required|string',
            'image' => 'nullable|image|max:102400',
        ]);

        $tutorial->fill($validated);
        $tutorial->is_active = $request->has('is_active');

        if ($request->hasFile('image')) {
            // Delete old image
            if ($tutorial->image_path && Storage::disk('public')->exists($tutorial->image_path)) {
                Storage::disk('public')->delete($tutorial->image_path);
            }
            $path = $request->file('image')->store('tutorials', 'public');
            $tutorial->image_path = $path;
        } elseif ($request->has('remove_image')) {
            // Delete image without replacement
            if ($tutorial->image_path && Storage::disk('public')->exists($tutorial->image_path)) {
                Storage::disk('public')->delete($tutorial->image_path);
            }
            $tutorial->image_path = null;
        }

        $tutorial->save();

        return redirect()->route('admin.tutorials.index')->with('success', 'Tutorial berhasil diperbarui.');
    }

    public function destroy(Tutorial $tutorial)
    {
        if ($tutorial->image_path && Storage::disk('public')->exists($tutorial->image_path)) {
            Storage::disk('public')->delete($tutorial->image_path);
        }
        
        $tutorial->delete();

        return redirect()->route('admin.tutorials.index')->with('success', 'Tutorial berhasil dihapus.');
    }
}
