<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\Department;
use App\Models\User;
use App\Models\Category;
use App\Services\AssetService;
use App\Support\AssetModuleNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(private AssetService $assetService)
    {
        $this->authorizeResource(Asset::class, 'asset');
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

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $this->assetService->update($asset, $request->validated(), $request->user());

        return redirect()
            ->to(AssetModuleNavigation::safeReturnUrl($request) ?? AssetModuleNavigation::routeForAsset($asset->fresh()))
            ->with('success', 'Asset diperbarui.');
    }

    public function updateAssignee(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('update', $asset);

        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'assigned_to_name' => ['nullable', 'string', 'max:255'],
        ]);

        $this->assetService->update($asset, $data, $request->user());

        return redirect()
            ->to(AssetModuleNavigation::safeReturnUrl($request) ?? AssetModuleNavigation::routeForAsset($asset->fresh()))
            ->with('success', 'Asset assignment updated.');
    }

    public function destroy(Request $request, Asset $asset): RedirectResponse
    {
        $this->assetService->delete($asset, auth()->user());

        return redirect()
            ->to(AssetModuleNavigation::safeReturnUrl($request) ?? AssetModuleNavigation::routeForAsset($asset))
            ->with('success', 'Asset dihapus.');
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
