<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetBast;
use App\Models\BorrowLog;
use App\Models\Department;
use App\Models\User;
use App\Support\AssetModuleNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetBastController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $type = $request->query('type');
        $status = $request->query('status');

        $basts = AssetBast::query()
            ->with(['asset', 'recipientUser', 'department', 'creator'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('document_number', 'like', "%{$search}%")
                        ->orWhere('recipient_name', 'like', "%{$search}%")
                        ->orWhere('recipient_email', 'like', "%{$search}%")
                        ->orWhereHas('asset', function ($assetQuery) use ($search) {
                            $assetQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('asset_code', 'like', "%{$search}%")
                                ->orWhere('serial_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($type, fn ($query) => $query->where('bast_type', $type))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('bast_date')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => AssetBast::count(),
            'issued' => AssetBast::where('status', AssetBast::STATUS_ISSUED)->count(),
            'signed' => AssetBast::where('status', AssetBast::STATUS_SIGNED)->count(),
            'this_month' => AssetBast::whereBetween('bast_date', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ];

        return view('assets.bast.index', compact('basts', 'search', 'type', 'status', 'stats'));
    }

    public function create(Request $request)
    {
        $selectedLoan = null;
        $selectedAsset = null;

        if ($request->filled('loan_id')) {
            $selectedLoan = BorrowLog::with(['asset', 'user.department', 'department'])
                ->whereIn('status', [BorrowLog::STATUS_APPROVED, BorrowLog::STATUS_RETURNED])
                ->whereNotNull('asset_id')
                ->find($request->integer('loan_id'));
            $selectedAsset = $selectedLoan?->asset;
        }

        if (! $selectedAsset && $request->filled('asset_id')) {
            $selectedAsset = Asset::with(['department', 'user'])->find($request->integer('asset_id'));
        }

        return view('assets.bast.create', [
            'assets' => Asset::orderBy('name')->get(['id', 'asset_code', 'name', 'serial_number', 'category', 'department_id', 'user_id']),
            'users' => User::orderBy('name')->get(['id', 'name', 'email', 'department_id']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'selectedAsset' => $selectedAsset,
            'selectedLoan' => $selectedLoan,
            'documentNumber' => $this->nextDocumentNumber(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_number' => ['nullable', 'string', 'max:100', 'unique:asset_basts,document_number'],
            'asset_id' => ['required', 'exists:assets,id'],
            'borrow_log_id' => ['nullable', 'exists:borrow_logs,id'],
            'recipient_user_id' => ['nullable', 'exists:users,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'bast_type' => ['required', Rule::in(AssetBast::TYPES)],
            'status' => ['required', Rule::in(AssetBast::STATUSES)],
            'bast_date' => ['required', 'date'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'handover_location' => ['nullable', 'string', 'max:255'],
            'condition_summary' => ['nullable', Rule::in(array_values(AssetBast::CONDITION_SUMMARY_OPTIONS))],
            'accessories' => ['nullable', 'array'],
            'accessories.*' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $asset = Asset::with(['department', 'user'])->findOrFail($validated['asset_id']);
        $recipientUser = ! empty($validated['recipient_user_id'])
            ? User::with('department')->find($validated['recipient_user_id'])
            : null;
        $departmentId = ($validated['department_id'] ?? null) ?: $recipientUser?->department_id ?: $asset->department_id;
        $department = $departmentId
            ? Department::find($departmentId)
            : null;

        $validated['document_number'] = ($validated['document_number'] ?? null) ?: $this->nextDocumentNumber();
        $validated['recipient_email'] = ($validated['recipient_email'] ?? null) ?: $recipientUser?->email;
        $validated['department_id'] = $departmentId;
        $validated['recipient_department'] = $department?->name;
        $validated['created_by'] = $request->user()?->id;
        $validated['signed_at'] = $validated['status'] === AssetBast::STATUS_SIGNED ? now() : null;
        $validated['asset_snapshot'] = $this->assetSnapshot($asset);
        $validated['accessories'] = array_values(array_filter($validated['accessories'] ?? []));
        $validated['photos'] = $this->storePhotos($request);

        $bast = AssetBast::create($validated);

        return redirect()
            ->to(AssetModuleNavigation::safeReturnUrl($request) ?? route('admin.assets.bast.show', $bast))
            ->with('success', 'BAST berhasil dibuat.');
    }

    public function show(AssetBast $bast)
    {
        return view('assets.bast.show', [
            'bast' => $bast->load(['asset.department', 'asset.user', 'recipientUser.department', 'department', 'borrowLog', 'creator']),
        ]);
    }

    public function print(AssetBast $bast)
    {
        return view('assets.bast.print', [
            'bast' => $bast->load(['asset.department', 'asset.user', 'recipientUser.department', 'department', 'borrowLog', 'creator']),
        ]);
    }

    private function nextDocumentNumber(): string
    {
        $prefix = 'BAST/' . now()->format('Ym');
        $count = AssetBast::withTrashed()
            ->where('document_number', 'like', $prefix . '/%')
            ->count() + 1;

        return $prefix . '/' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    private function assetSnapshot(Asset $asset): array
    {
        return [
            'asset_code' => $asset->asset_code,
            'name' => $asset->name,
            'category' => $asset->category,
            'serial_number' => $asset->serial_number,
            'hostname' => $asset->hostname,
            'brand' => $asset->brand,
            'model' => $asset->model,
            'status' => $asset->status,
            'condition' => $asset->condition,
            'location' => $asset->location,
            'department' => $asset->department?->name,
            'assigned_user' => $asset->assigned_to_display_name,
        ];
    }

    private function storePhotos(Request $request): array
    {
        return collect($request->file('photos', []))
            ->filter()
            ->map(function ($file) {
                return [
                    'path' => $file->store('asset-documents/bast', 'public'),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            })
            ->values()
            ->all();
    }
}
