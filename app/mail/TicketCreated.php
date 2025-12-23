<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Category;

class TicketController extends Controller
{
    /**
     * Tampilkan form create ticket (tanpa login)
     */
    public function create()
    {
        // Ambil semua kategori dari database
        $categories = Category::all();

        // Kirim ke view
        return view('tickets.create', compact('categories'));
    }

    /**
     * Simpan ticket baru ke database
     */
    public function store(Request $request)
    {
        // Validasi form
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
        ]);

        // Simpan ticket
        Ticket::create([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'status' => 'open', // default status
        ]);

        // Redirect dengan notifikasi sukses
        return redirect()->back()->with('success', '✅ Tiket berhasil dikirim! Tim IT akan segera menindaklanjuti.');
    }

    /**
     * Tampilkan daftar tiket (untuk admin)
     */
    public function index()
    {
        $tickets = Ticket::with('category')->latest()->get();
        return view('tickets.index', compact('tickets'));
    }
}
