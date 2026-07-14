<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RemoteSystemController;
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

Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'id', 'ko'])) {
        session()->put('locale', $locale);
    }
    return redirect()->back();
})->name('lang.switch');

// ✅ ROUTE LANDING
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('auth.login');
});

Route::get('/dashboard', function (Request $request) {
    $user = auth()->user();
    $hasDashboardAccess = $user && $user->hasDashboardAccess();
    $isAdmin = $user && $user->isAdmin();
    $isTechnician = $user && $user->isTechnician();

    $baseQuery = Ticket::query();

    if ($isTechnician) {
        $baseQuery->where('assigned_admin_id', $user->id);
    } elseif (! $isAdmin) {
        $baseQuery->where('user_id', $user?->id);
    }

    $categories = Category::orderBy('name')->get();
    $departments = Department::orderBy('name')->get();
    $recentTickets = (clone $baseQuery)->with(['category', 'department', 'attachments'])->latest()->take(5)->get();

    $totalTickets = (clone $baseQuery)->count();
    $openTickets = (clone $baseQuery)->where('status', 'open')->count();
    $inProgressTickets = (clone $baseQuery)->where('status', 'in_progress')->count();
    $resolvedTickets = (clone $baseQuery)->whereIn('status', ['resolved', 'closed'])->count();

    $highPriorityTickets = 0;
    $highPriorityList = collect();
    $ticketsByCategory = collect();
    $liveMonitoringQueue = collect();

    if ($hasDashboardAccess) {
        $highPriorityQuery = (clone $baseQuery)->where('priority', 'high')->whereNotIn('status', ['resolved', 'closed']);
        $highPriorityTickets = $highPriorityQuery->count();
        $highPriorityList = $highPriorityQuery->with(['category', 'department', 'user'])->latest()->take(5)->get();

        $ticketsByCategory = \Illuminate\Support\Facades\DB::table('tickets')
            ->selectRaw('category_id, count(*) as count')
            ->whereNotIn('status', ['resolved', 'closed'])
            ->groupBy('category_id')
            ->orderByDesc('count')
            ->get()->map(function($item) use ($categories) {
            return [
                'name' => $categories->firstWhere('id', $item->category_id)?->name ?? 'Unknown',
                'count' => $item->count
            ];
        });

        // NOC Live Queue: Open & In Progress, sorted by urgency (High first) then by oldest waiting time
        $liveMonitoringQueue = (clone $baseQuery)
            ->whereIn('status', ['open', 'in_progress'])
            ->with(['category', 'department', 'user'])
            ->orderByRaw("CASE WHEN priority = 'high' THEN 1 WHEN priority = 'medium' THEN 2 ELSE 3 END")
            ->oldest() // Oldest indicates waiting the longest
            ->limit(20)
            ->get();

        // --- Executive Metrics ---
        $today = now()->startOfDay();
        $totalTicketsToday = (clone $baseQuery)->where('created_at', '>=', $today)->count();
        $resolvedToday = (clone $baseQuery)->whereIn('status', ['resolved', 'closed'])->where('updated_at', '>=', $today)->count();

        // Yesterday comparison for KPI trend indicators
        $yesterday = now()->subDay()->startOfDay();
        $totalTicketsYesterday = (clone $baseQuery)->where('created_at', '>=', $yesterday)->where('created_at', '<', $today)->count();
        $resolvedYesterday = (clone $baseQuery)->whereIn('status', ['resolved', 'closed'])->where('updated_at', '>=', $yesterday)->where('updated_at', '<', $today)->count();
        $activeTicketsNow = $openTickets + $inProgressTickets;

        // Ticket Trend Chart Data (30 Days: Created vs Resolved)
        // For the frontend we'll send 30 days of data and the frontend can toggle to 7.
        // Or we just send 30 days and the charts handles it. Let's send 30 and 90 raw data arrays for JavaScript.
        $trendData30 = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $trendData30->put($date->format('Y-m-d'), ['created' => 0, 'resolved' => 0]);
        }

        $createdTrendQuery = \Illuminate\Support\Facades\DB::table('tickets')
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->get();

        $resolvedTrendQuery = \Illuminate\Support\Facades\DB::table('tickets')
            ->selectRaw('DATE(updated_at) as date, count(*) as count')
            ->whereIn('status', ['resolved', 'closed'])
            ->where('updated_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->get();

        foreach ($createdTrendQuery as $row) {
            if ($trendData30->has($row->date)) {
                $item = $trendData30->get($row->date);
                $item['created'] = $row->count;
                $trendData30->put($row->date, $item);
            }
        }
        foreach ($resolvedTrendQuery as $row) {
            if ($trendData30->has($row->date)) {
                $item = $trendData30->get($row->date);
                $item['resolved'] = $row->count;
                $trendData30->put($row->date, $item);
            }
        }

        $trendData = [
            'labels' => $trendData30->keys()->map(fn($date) => Carbon\Carbon::parse($date)->format('M d'))->values(),
            'created' => $trendData30->pluck('created')->values(),
            'resolved' => $trendData30->pluck('resolved')->values(),
        ];

        // Incident Heatmap Data (Tickets by Department)
        $departmentHeatmapQuery = \Illuminate\Support\Facades\DB::table('tickets')
            ->join('departments', 'tickets.department_id', '=', 'departments.id')
            ->selectRaw('departments.name as department_name,
                         SUM(CASE WHEN tickets.status NOT IN (\'resolved\', \'closed\') THEN 1 ELSE 0 END) as open_count,
                         SUM(CASE WHEN tickets.status IN (\'resolved\', \'closed\') THEN 1 ELSE 0 END) as resolved_count,
                         COUNT(tickets.id) as total_count')
            ->where('tickets.created_at', '>=', now()->subDays(30))
            ->groupBy('departments.name')
            ->orderByDesc('total_count')
            ->limit(10)
            ->get();

        // Category Heatmap Data
        $categoryHeatmapQuery = \Illuminate\Support\Facades\DB::table('tickets')
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category_name,
                         SUM(CASE WHEN tickets.status NOT IN (\'resolved\', \'closed\') THEN 1 ELSE 0 END) as open_count,
                         SUM(CASE WHEN tickets.status IN (\'resolved\', \'closed\') THEN 1 ELSE 0 END) as resolved_count,
                         COUNT(tickets.id) as total_count')
            ->where('tickets.created_at', '>=', now()->subDays(30))
            ->groupBy('categories.name')
            ->orderByDesc('total_count')
            ->limit(10)
            ->get();

        // Assets / Infrastructure Overview
        try {
            $assetOverview = [
                'total' => \Illuminate\Support\Facades\DB::table('assets')->whereNull('deleted_at')->count(),
                'active' => \Illuminate\Support\Facades\DB::table('assets')->whereNull('deleted_at')->where('status', 'in_use')->count(),
                'available' => \Illuminate\Support\Facades\DB::table('assets')->whereNull('deleted_at')->where('status', 'available')->count(),
                'maintenance' => \Illuminate\Support\Facades\DB::table('assets')->whereNull('deleted_at')->where('status', 'maintenance')->count(),
                'broken' => \Illuminate\Support\Facades\DB::table('assets')->whereNull('deleted_at')->where('status', 'broken')->count(),
            ];
        } catch (\Exception $e) {
            // Fallback if Asset table doesn't have those exact string values or doesn't exist
            $assetOverview = [
                'total' => 0, 'active' => 0, 'available' => 0, 'maintenance' => 0, 'broken' => 0
            ];
        }

        // Technician Performance
        $technicians = \Illuminate\Support\Facades\DB::table('users')
            ->whereIn('role', ['admin', 'Admin'])
            ->orWhere('is_admin', true)
            ->select('id', 'name', 'email')
            ->get()
            ->map(function ($tech) use ($baseQuery) {
                $assignedTickets = (clone $baseQuery)->where('assigned_admin_id', $tech->id);
                $openCount = (clone $assignedTickets)->whereNotIn('status', ['resolved', 'closed'])->count();
                $resolvedCount = (clone $assignedTickets)->whereIn('status', ['resolved', 'closed'])->count();

                // Calculate avg resolution time (in hours) for this tech's resolved tickets
                $resolvedTickets = (clone $assignedTickets)->whereIn('status', ['resolved', 'closed'])->get();
                $avgResTime = 0;
                if ($resolvedTickets->count() > 0) {
                    $totalMinutes = $resolvedTickets->sum(function($ticket) {
                         return $ticket->created_at->diffInMinutes($ticket->updated_at);
                    });
                    $avgResTime = round($totalMinutes / 60 / $resolvedTickets->count(), 1);
                }

                return [
                    'id' => $tech->id,
                    'name' => $tech->name,
                    'email' => $tech->email,
                    'total_assigned' => $openCount + $resolvedCount,
                    'avg_res_time' => $avgResTime,
                ];
            })->sortByDesc('total_assigned')->values();

        // SLA Breach (Naive calculation based on creation date vs priority thresholds)
        // High: 2h, Medium: 24h, Low: 48h
        $slaThresholds = [
            'high' => now()->subHours(2),
            'medium' => now()->subHours(24),
            'low' => now()->subHours(48),
        ];

        $slaBreachTickets = (clone $baseQuery)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->where(function ($query) use ($slaThresholds) {
                $query->where(function ($q) use ($slaThresholds) {
                    $q->where('priority', 'high')->where('created_at', '<', $slaThresholds['high']);
                })->orWhere(function ($q) use ($slaThresholds) {
                    $q->where('priority', 'medium')->where('created_at', '<', $slaThresholds['medium']);
                })->orWhere(function ($q) use ($slaThresholds) {
                    $q->where('priority', 'low')->where('created_at', '<', $slaThresholds['low']);
                });
            })
            ->with(['category', 'user', 'assignedAdmin'])
            ->orderBy('created_at')
            ->get();

        $slaBreachCount = $slaBreachTickets->count();

        // System Avg Resolution Time (Last 30 days)
        $globallyResolved = (clone $baseQuery)
            ->whereIn('status', ['resolved', 'closed'])
            ->where('updated_at', '>=', now()->subDays(30))
            ->get();

        $globalAvgResTime = 0;
        if ($globallyResolved->count() > 0) {
            $totalMins = $globallyResolved->sum(function($ticket) {
                 return $ticket->created_at->diffInMinutes($ticket->updated_at);
            });
            $globalAvgResTime = round($totalMins / 60 / $globallyResolved->count(), 1);
        }

        // Recent Activity Feed (Ticket Logs)
        $recentActivity = \App\Models\TicketLog::with(['ticket', 'user'])
            ->latest()
            ->take(10)
            ->get();
    }

    $checksumParts = [
        $totalTickets,
        $openTickets,
        $inProgressTickets,
        $resolvedTickets,
        $highPriorityTickets,
        $recentTickets->pluck('id')->join('-'),
        $recentTickets->pluck('updated_at')->map(fn ($date) => optional($date)->format('U') ?? '0')->join('-'),
    ];
    $checksum = hash('sha256', implode('|', $checksumParts));

    if ($request->boolean('refresh')) {
        $fragments = [];

        $fragments['dashboard-stats'] = view($hasDashboardAccess ? 'dashboard.partials.stats-admin' : 'dashboard.partials.stats', [
            'totalTickets' => $totalTickets,
            'openTickets' => $openTickets,
            'inProgressTickets' => $inProgressTickets,
            'resolvedTickets' => $resolvedTickets,
            'highPriorityTickets' => $highPriorityTickets,
        ])->render();
        $fragments['dashboard-history'] = view('dashboard.partials.history', [
            'recentTickets' => $recentTickets,
            'totalTickets' => $totalTickets,
            'isAdmin' => $isAdmin,
        ])->render();

        if ($hasDashboardAccess) {
            $fragments['dashboard-live-queue'] = view('dashboard.partials.live-queue', [
                'liveMonitoringQueue' => $liveMonitoringQueue
            ])->render();
        }

        return response()->json([
            'checksum' => $checksum,
            'fragments' => $fragments,
        ]);
    }

    $viewData = [
        'categories' => $categories,
        'recentTickets' => $recentTickets,
        'totalTickets' => $totalTickets,
        'openTickets' => $openTickets,
        'inProgressTickets' => $inProgressTickets,
        'resolvedTickets' => $resolvedTickets,
        'highPriorityTickets' => $highPriorityTickets,
        'highPriorityList' => $highPriorityList,
        'ticketsByCategory' => $ticketsByCategory,
        'liveMonitoringQueue' => $liveMonitoringQueue,
        'isAdmin' => $isAdmin,
        'hasDashboardAccess' => $hasDashboardAccess,
        'departments' => $departments,
        'checksum' => $checksum,
    ];

    if ($hasDashboardAccess) {
        $viewData = array_merge($viewData, [
            'totalTicketsToday' => $totalTicketsToday ?? 0,
            'resolvedToday' => $resolvedToday ?? 0,
            'totalTicketsYesterday' => $totalTicketsYesterday ?? 0,
            'resolvedYesterday' => $resolvedYesterday ?? 0,
            'trendData' => $trendData ?? [],
            'technicians' => $technicians ?? collect(),
            'slaBreachCount' => $slaBreachCount ?? 0,
            'slaBreachTickets' => $slaBreachTickets ?? collect(),
            'globalAvgResTime' => $globalAvgResTime ?? 0,
            'recentActivity' => $recentActivity ?? collect(),
            'departmentHeatmap' => $departmentHeatmapQuery ?? collect(),
            'categoryHeatmap' => $categoryHeatmapQuery ?? collect(),
            'assetOverview' => $assetOverview ?? ['total'=>0,'active'=>0,'maintenance'=>0,'broken'=>0],
        ]);
    }

    $viewName = $hasDashboardAccess ? 'dashboard-admin' : 'dashboard';

    return view($viewName, $viewData);
})->middleware(['auth', 'approved'])->name('dashboard');

Route::middleware(['auth', 'approved'])->group(function () {
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

    Route::get('/tutorials', [App\Http\Controllers\TutorialController::class, 'index'])->name('tutorials.index');
    Route::get('/tutorials/{slug}', [App\Http\Controllers\TutorialController::class, 'show'])->name('tutorials.show');
});

// ✅ ROUTE UNTUK ADMIN (login / dashboard)
Route::middleware(['auth', 'approved', 'admin'])->group(function () {
    Route::get('/admin/settings', App\Livewire\Admin\Settings\Index::class)
        ->middleware('super_admin')
        ->name('admin.settings.index');
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.updateStatus');
    Route::get('/admin/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/admin/users', [UserController::class, 'store'])->name('users.store');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/admin/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
    Route::post('/admin/users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
    Route::post('/admin/users/{user}/reject', [UserController::class, 'reject'])->name('users.reject');
    Route::post('/admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
    Route::post('/loans/{loan}/status', [App\Http\Controllers\LoanController::class, 'updateStatus'])->name('loans.updateStatus');
    Route::delete('/loans/{loan}', [App\Http\Controllers\LoanController::class, 'destroy'])->name('loans.destroy');
    Route::get('/admin/assets', [App\Http\Controllers\AssetCenterController::class, 'overview'])->name('assets.index');
    Route::get('/admin/assets/locations', [App\Http\Controllers\AssetController::class, 'locations'])->name('assets.locations');
    Route::get('/admin/assets/departments', [App\Http\Controllers\AssetController::class, 'departments'])->name('assets.departments');
    Route::get('/admin/assets/user-assets', [App\Http\Controllers\AssetController::class, 'userAssets'])->name('assets.userAssets');
    Route::get('/admin/assets/detail', [App\Http\Controllers\AssetController::class, 'assetDetail'])->name('assets.detail');
    Route::get('/admin/assets/create', [App\Http\Controllers\AssetController::class, 'create'])->name('assets.create');

    // Legacy Asset Center overview URL now resolves to the unified asset dashboard.
    Route::redirect('/admin/assets/overview', '/admin/assets')->name('admin.assets.overview');

    // Category Pages
    Route::redirect('/admin/assets/pc-laptop', '/admin/assets/pc');
    Route::get('/admin/assets/pc', [App\Http\Controllers\AssetCenterController::class, 'pcIndex'])->name('admin.assets.pc');
    Route::get('/admin/assets/laptop', [App\Http\Controllers\AssetCenterController::class, 'laptopIndex'])->name('admin.assets.laptop');
    Route::get('/admin/assets/monitor', [App\Http\Controllers\AssetCenterController::class, 'monitorIndex'])->name('admin.assets.monitor');
    Route::get('/admin/assets/printer-scanner', [App\Http\Controllers\AssetCenterController::class, 'printerScannerIndex'])->name('admin.assets.printer-scanner');
    Route::get('/admin/assets/network-device', [App\Http\Controllers\AssetCenterController::class, 'networkDeviceIndex'])->name('admin.assets.network-device');
    Route::get('/admin/assets/cctv', [App\Http\Controllers\AssetCenterController::class, 'cctvIndex'])->name('admin.assets.cctv');
    Route::get('/admin/assets/peripheral', [App\Http\Controllers\AssetCenterController::class, 'peripheralIndex'])->name('admin.assets.peripheral');
    Route::get('/admin/assets/software-license', [App\Http\Controllers\AssetCenterController::class, 'softwareLicenseIndex'])->name('admin.assets.software-license');

    // Manual Inventory CRUD
    Route::get('/admin/assets/manual', [App\Http\Controllers\AssetCenterController::class, 'manualIndex'])->name('admin.assets.manual.index');
    Route::get('/admin/assets/manual/create', [App\Http\Controllers\AssetCenterController::class, 'manualCreate'])->name('admin.assets.manual.create');
    Route::post('/admin/assets/manual', [App\Http\Controllers\AssetCenterController::class, 'manualStore'])->name('admin.assets.manual.store');
    Route::get('/admin/assets/manual/{asset}/edit', [App\Http\Controllers\AssetCenterController::class, 'manualEdit'])->name('admin.assets.manual.edit')->whereNumber('asset');
    Route::put('/admin/assets/manual/{asset}', [App\Http\Controllers\AssetCenterController::class, 'manualUpdate'])->name('admin.assets.manual.update')->whereNumber('asset');
    Route::delete('/admin/assets/manual/{asset}', [App\Http\Controllers\AssetCenterController::class, 'manualDestroy'])->name('admin.assets.manual.destroy')->whereNumber('asset');

    Route::get('/admin/assets/assignment', [App\Http\Controllers\AssetCenterController::class, 'assignment'])->name('admin.assets.assignment');
    Route::get('/admin/assets/audit-log', [App\Http\Controllers\AssetCenterController::class, 'auditLog'])->name('admin.assets.audit-log');
    Route::patch('/admin/assets/{asset}/lifecycle', [App\Http\Controllers\AssetCenterController::class, 'updateLifecycle'])->name('admin.assets.lifecycle.update')->whereNumber('asset');
    Route::patch('/admin/assets/{asset}/assignee', [App\Http\Controllers\AssetController::class, 'updateAssignee'])->name('assets.assignee.update')->whereNumber('asset');
    Route::get('/admin/assets/import-export', [App\Http\Controllers\AssetCenterController::class, 'importExport'])->name('admin.assets.import-export');
    Route::post('/admin/assets/import', [App\Http\Controllers\AssetCenterController::class, 'import'])->name('admin.assets.import');
    Route::get('/admin/assets/export', [App\Http\Controllers\AssetCenterController::class, 'export'])->name('admin.assets.export');

    Route::get('/admin/assets/bast', [App\Http\Controllers\AssetBastController::class, 'index'])->name('admin.assets.bast.index');
    Route::get('/admin/assets/bast/create', [App\Http\Controllers\AssetBastController::class, 'create'])->name('admin.assets.bast.create');
    Route::post('/admin/assets/bast', [App\Http\Controllers\AssetBastController::class, 'store'])->name('admin.assets.bast.store');
    Route::get('/admin/assets/bast/{bast}', [App\Http\Controllers\AssetBastController::class, 'show'])->name('admin.assets.bast.show')->whereNumber('bast');
    Route::get('/admin/assets/bast/{bast}/print', [App\Http\Controllers\AssetBastController::class, 'print'])->name('admin.assets.bast.print')->whereNumber('bast');

    Route::get('/admin/assets/inspections', [App\Http\Controllers\AssetInspectionController::class, 'index'])->name('admin.assets.inspections.index');
    Route::get('/admin/assets/inspections/create', [App\Http\Controllers\AssetInspectionController::class, 'create'])->name('admin.assets.inspections.create');
    Route::post('/admin/assets/inspections', [App\Http\Controllers\AssetInspectionController::class, 'store'])->name('admin.assets.inspections.store');
    Route::get('/admin/assets/inspections/{inspection}', [App\Http\Controllers\AssetInspectionController::class, 'show'])->name('admin.assets.inspections.show')->whereNumber('inspection');
    Route::get('/admin/assets/inspections/{inspection}/print', [App\Http\Controllers\AssetInspectionController::class, 'print'])->name('admin.assets.inspections.print')->whereNumber('inspection');

    Route::get('/admin/assets/reports', [App\Http\Controllers\AssetReportController::class, 'index'])->name('admin.assets.reports.index');

    // Asset Relationship routes
    Route::post('/admin/assets/{asset}/relations', [App\Http\Controllers\AssetCenterController::class, 'attachRelation'])->name('admin.assets.relations.attach')->whereNumber('asset');
    Route::post('/admin/assets/{asset}/relations/parent', [App\Http\Controllers\AssetCenterController::class, 'attachParentRelation'])->name('admin.assets.relations.attach-parent')->whereNumber('asset');
    Route::patch('/admin/assets/relations/{relation}/detach', [App\Http\Controllers\AssetCenterController::class, 'detachRelation'])->name('admin.assets.relations.detach');

    // Override original show detail route
    Route::get('/admin/assets/{asset}', [App\Http\Controllers\AssetCenterController::class, 'showDetail'])->name('assets.show')->whereNumber('asset');
    Route::get('/admin/assets/{asset}/edit', [App\Http\Controllers\AssetController::class, 'edit'])->name('assets.edit')->whereNumber('asset');
    Route::post('/admin/assets', [App\Http\Controllers\AssetController::class, 'store'])->name('assets.store');
    Route::put('/admin/assets/{asset}', [App\Http\Controllers\AssetController::class, 'update'])->name('assets.update')->whereNumber('asset');
    Route::delete('/admin/assets/{asset}', [App\Http\Controllers\AssetController::class, 'destroy'])->name('assets.destroy')->whereNumber('asset');

    // Chat Conversations
    Route::get('/admin/conversations', [App\Http\Controllers\Admin\ConversationController::class, 'index'])->name('admin.conversations.index');
    Route::get('/admin/conversations/{conversation}', [App\Http\Controllers\Admin\ConversationController::class, 'show'])->name('admin.conversations.show');
    Route::post('/admin/conversations/{conversation}/reply', [App\Http\Controllers\Admin\ConversationController::class, 'reply'])->name('admin.conversations.reply');

    // Admin Tutorials
    Route::resource('admin/tutorials', App\Http\Controllers\Admin\TutorialController::class, ['as' => 'admin']);
    Route::post('/admin/conversations/{conversation}/close', [App\Http\Controllers\Admin\ConversationController::class, 'close'])->name('admin.conversations.close');
    Route::post('/admin/conversations/{conversation}/reopen', [App\Http\Controllers\Admin\ConversationController::class, 'reopen'])->name('admin.conversations.reopen');

    Route::get('/admin/notifications/summary', AdminNotificationSummaryController::class)->name('admin.notifications.summary');

    // Reports
    Route::get('/admin/reports/export', [App\Http\Controllers\ReportController::class, 'export'])->name('reports.export');

    // Remote System
    Route::get('/remote-system', [RemoteSystemController::class, 'index'])->name('remote-system.index');
    Route::get('/remote-system/ping', [RemoteSystemController::class, 'ping'])->name('remote-system.ping');
});

require __DIR__.'/auth.php';
