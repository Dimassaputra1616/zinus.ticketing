<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminNotificationSummaryController;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Mail\TicketStatusUpdatedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ✅ ROUTE LANDING
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('auth.login');
});

Route::get('/dashboard', function (Request $request) {
    $user = auth()->user();
    $isAdmin = $user && $user->isAdmin();

    $baseQuery = Ticket::query();

    if (! $isAdmin) {
        $baseQuery->where('user_id', $user?->id);
    }

    $categories = Category::orderBy('name')->get();
    $departments = Department::orderBy('name')->get();
    $recentTickets = (clone $baseQuery)->with(['category', 'department', 'attachments'])->latest()->take(5)->get();

    $totalTickets = (clone $baseQuery)->count();
    $openTickets = (clone $baseQuery)->where('status', 'open')->count();
    $inProgressTickets = (clone $baseQuery)->where('status', 'in_progress')->count();
    $resolvedTickets = (clone $baseQuery)->whereIn('status', ['resolved', 'closed'])->count();

    $checksumParts = [
        $totalTickets,
        $openTickets,
        $inProgressTickets,
        $resolvedTickets,
        $recentTickets->pluck('id')->join('-'),
        $recentTickets->pluck('updated_at')->map(fn ($date) => optional($date)->format('U') ?? '0')->join('-'),
    ];
    $checksum = hash('sha256', implode('|', $checksumParts));

    if ($request->boolean('refresh')) {
        return response()->json([
            'checksum' => $checksum,
            'fragments' => [
                'dashboard-stats' => view('dashboard.partials.stats', [
                    'totalTickets' => $totalTickets,
                    'openTickets' => $openTickets,
                    'inProgressTickets' => $inProgressTickets,
                    'resolvedTickets' => $resolvedTickets,
                ])->render(),
                'dashboard-history' => view('dashboard.partials.history', [
                    'recentTickets' => $recentTickets,
                    'totalTickets' => $totalTickets,
                    'isAdmin' => $isAdmin,
                ])->render(),
            ],
        ]);
    }

    return view('dashboard', [
        'categories' => $categories,
        'recentTickets' => $recentTickets,
        'totalTickets' => $totalTickets,
        'openTickets' => $openTickets,
        'inProgressTickets' => $inProgressTickets,
        'resolvedTickets' => $resolvedTickets,
        'isAdmin' => $isAdmin,
        'departments' => $departments,
        'checksum' => $checksum,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [TicketAttachmentController::class, 'download'])->name('tickets.attachments.download');
    Route::get('/my-tickets', [TicketController::class, 'myTickets'])->name('tickets.mine');
    Route::get('/my-tickets/{ticket}', [TicketController::class, 'show'])->name('user.tickets.show');
    Route::get('/loans', [App\Http\Controllers\LoanController::class, 'index'])->name('loans.index');
    Route::post('/loans', [App\Http\Controllers\LoanController::class, 'store'])->name('loans.store');
    Route::post('/loans/device', [App\Http\Controllers\LoanController::class, 'storeDevice'])->name('loans.device.store');
    Route::get('/logout', function (Request $request) {
        if (auth()->check()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login');
    })->name('logout.fallback');
});

// ✅ ROUTE UNTUK ADMIN (login / dashboard)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.updateStatus');
    Route::get('/admin/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/admin/users', [UserController::class, 'store'])->name('users.store');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/admin/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
    Route::post('/admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
    Route::post('/loans/{loan}/status', [App\Http\Controllers\LoanController::class, 'updateStatus'])->name('loans.updateStatus');
    Route::delete('/loans/{loan}', [App\Http\Controllers\LoanController::class, 'destroy'])->name('loans.destroy');
    Route::get('/admin/assets', [App\Http\Controllers\AssetController::class, 'index'])->name('assets.index');
    Route::get('/admin/assets/locations', [App\Http\Controllers\AssetController::class, 'locations'])->name('assets.locations');
    Route::get('/admin/assets/departments', [App\Http\Controllers\AssetController::class, 'departments'])->name('assets.departments');
    Route::get('/admin/assets/user-assets', [App\Http\Controllers\AssetController::class, 'userAssets'])->name('assets.userAssets');
    Route::get('/admin/assets/detail', [App\Http\Controllers\AssetController::class, 'assetDetail'])->name('assets.detail');
    Route::get('/admin/assets/create', [App\Http\Controllers\AssetController::class, 'create'])->name('assets.create');
    Route::get('/admin/assets/{asset}', [App\Http\Controllers\AssetController::class, 'show'])->name('assets.show');
    Route::get('/admin/assets/{asset}/edit', [App\Http\Controllers\AssetController::class, 'edit'])->name('assets.edit');
    Route::post('/admin/assets', [App\Http\Controllers\AssetController::class, 'store'])->name('assets.store');
    Route::put('/admin/assets/{asset}', [App\Http\Controllers\AssetController::class, 'update'])->name('assets.update');
    Route::delete('/admin/assets/{asset}', [App\Http\Controllers\AssetController::class, 'destroy'])->name('assets.destroy');
    Route::get('/admin/notifications/summary', AdminNotificationSummaryController::class)->name('admin.notifications.summary');
});

require __DIR__.'/auth.php';
