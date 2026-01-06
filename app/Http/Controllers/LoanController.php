<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BorrowLog;
use App\Models\Department;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user?->isAdmin();

        $statuses = [
            BorrowLog::STATUS_WAITING => 'Waiting',
            BorrowLog::STATUS_APPROVED => 'Approved',
            BorrowLog::STATUS_RETURNED => 'Returned',
            BorrowLog::STATUS_REJECTED => 'Rejected',
        ];

        $search = trim((string) $request->query('search', ''));
        $statusFilter = $request->query('status');
        $assetFilter = $request->query('asset_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $logs = BorrowLog::with(['department', 'user.department', 'asset', 'device', 'processedBy'])
            ->when(! $isAdmin, fn ($query) => $query->where('user_id', $user?->id))
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                })->orWhereHas('asset', function ($assetQuery) use ($search) {
                    $assetQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('asset_code', 'like', '%' . $search . '%');
                })->orWhereHas('device', function ($deviceQuery) use ($search) {
                    $deviceQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%');
                });
            })
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->when($assetFilter, fn ($query) => $query->where('asset_id', $assetFilter))
            ->when($startDate, fn ($query) => $query->whereDate('start_date', '>=', Carbon::parse($startDate)))
            ->when($endDate, fn ($query) => $query->whereDate('end_date', '<=', Carbon::parse($endDate)))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $assets = Asset::query()
            ->where('status', 'available')
            ->whereNull('user_id')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'asset_code', 'name', 'category', 'category_id']);

        $spareDevicesQuery = Asset::query()
            ->where('status', 'available')
            ->whereNull('user_id')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('category', 'Laptop')
                    ->orWhereHas('categoryRel', fn ($categoryQuery) => $categoryQuery->where('name', 'Laptop'));
            })
            ->with('categoryRel');

        $spareDevices = $spareDevicesQuery->orderBy('name')->get(['id', 'asset_code', 'name', 'category', 'category_id']);

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $statusBadge = [
            BorrowLog::STATUS_WAITING => 'bg-amber-100 text-amber-700 border border-amber-200',
            BorrowLog::STATUS_APPROVED => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
            BorrowLog::STATUS_RETURNED => 'bg-slate-100 text-slate-700 border border-slate-200',
            BorrowLog::STATUS_REJECTED => 'bg-rose-100 text-rose-700 border border-rose-200',
        ];

        if ($request->boolean('fragment')) {
            return response()->json([
                'table' => view('loans.partials.table', compact('logs', 'statuses', 'isAdmin', 'statusBadge'))->render(),
            ]);
        }

        return view('loans.index', compact(
            'logs',
            'assets',
            'spareDevices',
            'departments',
            'statuses',
            'search',
            'statusFilter',
            'assetFilter',
            'startDate',
            'endDate',
            'isAdmin',
            'statusBadge'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'department_id' => 'required|exists:departments,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        $assetQuery = Asset::query()
            ->whereKey($request->asset_id)
            ->where('status', 'available')
            ->whereNull('user_id')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('category', 'Laptop')
                    ->orWhereHas('categoryRel', fn ($categoryQuery) => $categoryQuery->where('name', 'Laptop'));
            });

        if (! $assetQuery->exists()) {
            throw ValidationException::withMessages([
                'asset_id' => 'Device tidak tersedia untuk peminjaman.',
            ]);
        }

        BorrowLog::create([
            'user_id' => $request->user()->id,
            'department_id' => $request->department_id,
            'asset_id' => $request->asset_id,
            'start_date' => Carbon::parse($request->start_date),
            'end_date' => Carbon::parse($request->end_date),
            'reason' => $request->reason,
            'status' => BorrowLog::STATUS_WAITING,
        ]);

        return redirect()->route('loans.index')->with('ok', 'Pengajuan peminjaman dikirim.');
    }

    public function updateStatus(Request $request, BorrowLog $loan)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', [
                BorrowLog::STATUS_WAITING,
                BorrowLog::STATUS_APPROVED,
                BorrowLog::STATUS_RETURNED,
                BorrowLog::STATUS_REJECTED,
            ]),
            'asset_code' => 'nullable|string|max:255',
        ]);

        $status = $validated['status'];
        $loan->status = $status;
        $loan->processed_by = $user->id;
        $loan->processed_at = now();

        if ($status === BorrowLog::STATUS_APPROVED) {
            $assetCode = $validated['asset_code'] ?? null;
            if ($assetCode !== null && $assetCode !== '') {
                $loan->asset_code = $assetCode;
            }
        }

        if ($status === BorrowLog::STATUS_RETURNED) {
            $loan->returned_at = now();
        }

        $loan->save();

        return redirect()->route('loans.index')->with('ok', 'Status peminjaman diperbarui.');
    }

    public function storeDevice(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:devices,code',
            'category' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $device = Device::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Device baru ditambahkan.',
                'device' => $device,
            ]);
        }

        return redirect()->route('loans.index')->with('ok', 'Device baru ditambahkan.');
    }
}
