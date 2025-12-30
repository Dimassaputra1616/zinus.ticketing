<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Notifications\UserRegisteredNotification;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (! $user->isAdmin()) {
            abort(403, 'Akses ditolak - hanya untuk admin IT');
        }

        $search = trim((string) $request->query('q', ''));
        $perPage = 10;

        $usersQuery = User::query();

        $totalUsers = (clone $usersQuery)->count();
        $adminCount = (clone $usersQuery)->where('role', 'admin')->count();
        $staffCount = $totalUsers - $adminCount;

        if ($request->boolean('autocomplete')) {
            $suggestions = (clone $usersQuery)
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('role', 'like', '%' . $search . '%');
                    });
                })
                ->orderBy('name')
                ->limit(6)
                ->get(['id', 'name', 'email', 'role']);

            return response()->json([
                'suggestions' => $suggestions,
            ]);
        }

        $users = $usersQuery
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('role', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        if ($request->boolean('fragment')) {
            $table = view('users.partials.table', compact('users'))->render();

            return response()->json([
                'table' => $table,
            ]);
        }

        return view('users.index', compact('users', 'search', 'totalUsers', 'adminCount', 'staffCount'));
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,admin',
        ]);

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        User::query()
            ->where(function ($query) {
                $query->where('role', 'admin')
                    ->orWhere('is_admin', true);
            })
            ->get()
            ->each(fn (User $admin) => $admin->notify(new UserRegisteredNotification($newUser, $authUser)));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'User baru berhasil ditambahkan.',
                'user' => $newUser,
            ]);
        }

        return redirect()->route('users.index')->with('ok', 'User baru berhasil ditambahkan.');
    }

    public function updateRole(Request $request, User $user)
    {
        $authUser = Auth::user();

        if (! $authUser || ! $authUser->isSuperAdmin()) {
            abort(403, 'Akses ditolak - hanya untuk super admin');
        }

        $request->validate([
            'role' => 'required|in:user,admin',
        ]);

        $user->role = $request->role;
        $user->save();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Role user berhasil diperbarui.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ]);
        }

        return redirect()->route('users.index')->with('ok', 'Role user berhasil diperbarui.');
    }

    public function destroy(Request $request, $userId)
    {
        $authUser = Auth::user();

        if (! $authUser || ! $authUser->isSuperAdmin()) {
            abort(403, 'Akses ditolak - hanya untuk super admin');
        }

        $user = User::find($userId);

        if (! $user) {
            $message = 'User tidak ditemukan.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 404);
            }
            return redirect()->route('users.index')->withErrors(['delete' => $message]);
        }

        if ($user->id === $authUser->id) {
            $message = 'Tidak dapat menghapus akun yang sedang login.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->route('users.index')->withErrors(['delete' => $message]);
        }

        $user->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'User berhasil dihapus.',
            ]);
        }

        return redirect()->route('users.index')->with('ok', 'User berhasil dihapus.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $authUser = Auth::user();

        if (! $authUser || ! $authUser->isSuperAdmin()) {
            abort(403, 'Akses ditolak - hanya untuk super admin');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Password {$user->name} berhasil direset.",
            ]);
        }

        return redirect()->route('users.index')->with('ok', "Password {$user->name} berhasil direset.");
    }
}
