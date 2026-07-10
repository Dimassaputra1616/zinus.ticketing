<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetInspection;
use App\Support\AssetModuleNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetInspectionController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $type = $request->query('type');
        $result = $request->query('result');

        $inspections = AssetInspection::query()
            ->with(['asset', 'inspector'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('inspection_number', 'like', "%{$search}%")
                        ->orWhere('findings', 'like', "%{$search}%")
                        ->orWhere('action_required', 'like', "%{$search}%")
                        ->orWhereHas('asset', function ($assetQuery) use ($search) {
                            $assetQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('asset_code', 'like', "%{$search}%")
                                ->orWhere('serial_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($type, fn ($query) => $query->where('inspection_type', $type))
            ->when($result, fn ($query) => $query->where('result', $result))
            ->latest('inspection_date')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => AssetInspection::count(),
            'passed' => AssetInspection::where('result', AssetInspection::RESULT_PASSED)->count(),
            'needs_repair' => AssetInspection::where('result', AssetInspection::RESULT_NEEDS_REPAIR)->count(),
            'this_month' => AssetInspection::whereBetween('inspection_date', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ];

        return view('assets.inspections.index', compact('inspections', 'search', 'type', 'result', 'stats'));
    }

    public function create(Request $request)
    {
        return view('assets.inspections.create', [
            'assets' => Asset::orderBy('name')->get(['id', 'asset_code', 'name', 'serial_number', 'category']),
            'selectedAsset' => $request->filled('asset_id') ? Asset::find($request->integer('asset_id')) : null,
            'inspectionNumber' => $this->nextInspectionNumber(),
            'checklistItems' => $this->checklistItems(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inspection_number' => ['nullable', 'string', 'max:100', 'unique:asset_inspections,inspection_number'],
            'asset_id' => ['required', 'exists:assets,id'],
            'inspection_type' => ['required', Rule::in(AssetInspection::TYPES)],
            'inspection_date' => ['required', 'date'],
            'overall_condition' => ['required', Rule::in(AssetInspection::CONDITIONS)],
            'result' => ['required', Rule::in(AssetInspection::RESULTS)],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['nullable', 'in:ok,issue,na'],
            'findings' => ['nullable', 'string', 'max:5000'],
            'action_required' => ['nullable', 'string', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'next_inspection_date' => ['nullable', 'date', 'after_or_equal:inspection_date'],
        ]);

        $validated['inspection_number'] = ($validated['inspection_number'] ?? null) ?: $this->nextInspectionNumber();
        $validated['inspected_by'] = $request->user()?->id;
        $validated['checklist'] = $this->normalizeChecklist($validated['checklist'] ?? []);
        $validated['photos'] = $this->storePhotos($request);

        $inspection = AssetInspection::create($validated);

        return redirect()
            ->to(AssetModuleNavigation::safeReturnUrl($request) ?? route('admin.assets.inspections.show', $inspection))
            ->with('success', 'Inspection device berhasil disimpan.');
    }

    public function show(AssetInspection $inspection)
    {
        return view('assets.inspections.show', [
            'inspection' => $inspection->load(['asset.department', 'asset.user', 'inspector']),
            'checklistItems' => $this->checklistItems(),
        ]);
    }

    public function print(AssetInspection $inspection)
    {
        return view('assets.inspections.print', [
            'inspection' => $inspection->load(['asset.department', 'asset.user', 'inspector']),
            'checklistItems' => $this->checklistItems(),
        ]);
    }

    private function nextInspectionNumber(): string
    {
        $prefix = 'INSP/' . now()->format('Ym');
        $count = AssetInspection::withTrashed()
            ->where('inspection_number', 'like', $prefix . '/%')
            ->count() + 1;

        return $prefix . '/' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    private function checklistItems(): array
    {
        return [
            'power' => 'Power & booting',
            'display' => 'Display / screen',
            'keyboard_mouse' => 'Keyboard / mouse / input',
            'network' => 'Network / Wi-Fi / LAN',
            'storage' => 'Storage health',
            'ports' => 'Ports & peripherals',
            'physical' => 'Physical condition',
            'security' => 'Security / antivirus',
        ];
    }

    private function normalizeChecklist(array $checklist): array
    {
        return collect($this->checklistItems())
            ->mapWithKeys(fn ($label, $key) => [$key => $checklist[$key] ?? 'na'])
            ->all();
    }

    private function storePhotos(Request $request): array
    {
        return collect($request->file('photos', []))
            ->filter()
            ->map(function ($file) {
                return [
                    'path' => $file->store('asset-documents/inspections', 'public'),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            })
            ->values()
            ->all();
    }
}
