<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\Department;
use App\Models\User;
use App\Models\Category;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    public function __construct(private AssetService $assetService)
    {
        $this->authorizeResource(Asset::class, 'asset');
    }

    public function index(Request $request): View
    {
        $statusMap = [
            'active' => Asset::STATUS_IN_USE,
            'in_repair' => Asset::STATUS_MAINTENANCE,
            'spare' => Asset::STATUS_AVAILABLE,
            'retired' => Asset::STATUS_BROKEN,
        ];
        $statusKey = Str::of($request->query('status'))->snake()->lower()->toString();

        $filters = [
            'factory' => $request->query('factory'),
            'department' => $request->integer('department') ?: null,
            'category' => $request->query('category'),
            'status' => null,
            'search' => $request->query('search'),
        ];
        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;

        $base = Asset::query();
        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', $statusMap['active'])->count(),
            'in_repair' => (clone $base)->where('status', $statusMap['in_repair'])->count(),
            'spare' => (clone $base)->where('status', $statusMap['spare'])->count(),
            'retired' => (clone $base)->where('status', $statusMap['retired'])->count(),
        ];

        $assetsQuery = $this->assetService->filteredQuery($filters, false);

        if (array_key_exists($statusKey, $statusMap)) {
            $assetsQuery->where('status', $statusMap[$statusKey]);
        }

        return view('assets.index', [
            'assets' => $assetsQuery->paginate($perPage)->withQueryString(),
            'stats' => $stats,
            'departments' => Department::orderBy('name')->get(),
            'filters' => $filters,
            'perPage' => $perPage,
            'filterOptions' => [
                'factories' => [
                    'Zinus F1 Bogor',
                    'Zinus F2 Karawang',
                    'Zinus F3 Tangerang',
                ],
                'categories' => ['PC', 'Laptop', 'Monitor', 'Peripheral'],
                'statuses' => ['active', 'in_repair', 'spare', 'retired'],
            ],
        ]);
    }

    public function create(): View
    {
        return view('assets.create', [
            'asset' => null,
            'statusOptions' => Asset::STATUSES,
            'departments' => Department::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $this->assetService->store($request->validated(), $request->user());

        return redirect()->route('assets.index')->with('success', 'Asset disimpan.');
    }

    public function edit(Asset $asset): View
    {
        return view('assets.create', [
            'asset' => $asset,
            'statusOptions' => Asset::STATUSES,
            'departments' => Department::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function show(Asset $asset): View
    {
        return view('assets.show', [
            'asset' => $asset->load(['department', 'user']),
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $this->assetService->update($asset, $request->validated(), $request->user());

        return redirect()->route('assets.index')->with('success', 'Asset diperbarui.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $this->assetService->delete($asset, auth()->user());

        return redirect()->route('assets.index')->with('success', 'Asset dihapus.');
    }

    public function locations(Request $request): JsonResponse
    {
        $categoryId = $request->query('category_id');
        $categoryName = $categoryId ? optional(Category::find($categoryId))->name : null;

        return response()->json(
            $this->assetService->getLocationBreakdown($categoryName)->values()
        );
    }

    public function departments(Request $request): JsonResponse
    {
        $categoryId = $request->query('category_id');
        $categoryName = $categoryId ? optional(Category::find($categoryId))->name : null;
        $location = $request->query('location');

        return response()->json(
            $this->assetService->getDepartmentBreakdown($categoryName, $location)->values()
        );
    }

    public function userAssets(Request $request): JsonResponse
    {
        $categoryId = $request->query('category_id');
        $categoryName = $categoryId ? optional(Category::find($categoryId))->name : null;
        $location = $request->query('location');
        $departmentId = $request->query('department_id');
        $departmentId = $departmentId ? (int) $departmentId : null;
        $search = $request->query('search');

        return response()->json(
            $this->assetService->getUserAssetBreakdown($categoryName, $location, $departmentId, $search)->values()
        );
    }

    public function assetDetail(Request $request): JsonResponse
    {
        $assetId = $request->query('asset_id');
        $assetId = $assetId ? (int) $assetId : null;

        $payload = $this->assetService->getAssetDetailPayload($assetId);

        if (! $payload) {
            return response()->json(['message' => 'Asset tidak ditemukan.'], 404);
        }

        return response()->json($payload);
    }
}
