<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\BorrowLog;
use App\Models\Department;
use App\Models\User;
use App\Services\AssetService;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\AssetRelation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use InvalidArgumentException;

class AssetCenterController extends Controller
{
    public function __construct(private AssetService $assetService)
    {
        $this->middleware(['auth', 'admin']);
    }

    public function overview(Request $request)
    {
        // Specific category counts
        $totalPc = Asset::whereIn('category', ['PC', 'PC / Laptop', 'PC/Laptop'])->count();
        $totalLaptop = Asset::whereIn('category', ['Laptop', 'pc-laptop'])->count();
        $totalMonitor = Asset::whereIn('category', ['Monitor'])->count();
        $totalPrinterScanner = Asset::whereIn('category', ['Printer', 'Scanner', 'Printer & Scanner', 'printer-scanner'])->count();
        $totalNetwork = Asset::whereIn('category', ['Network Device', 'Router', 'Switch', 'Access Point', 'network-device'])->count();
        $totalCctv = Asset::whereIn('category', ['CCTV', 'NVR/DVR', 'cctv'])->count();
        $totalPeripheral = Asset::whereIn('category', ['Peripheral', 'Keyboard', 'Mouse', 'UPS', 'Projector', 'peripheral'])->count();
        $totalLicense = Asset::whereIn('category', ['Software License', 'License', 'software-license'])->count();

        $filters = [
            'search' => $request->query('search'),
            'factory' => $request->query('factory'),
            'department' => $request->integer('department') ?: null,
            'category' => $request->query('category'),
            'status' => $request->query('status'),
        ];
        $perPage = max(10, min((int) $request->query('per_page', 10), 100));
        $assets = $this->assetService
            ->filteredQuery($filters)
            ->paginate($perPage)
            ->withQueryString();

        $filterFactories = collect([
            'Zinus F1 Bogor',
            'Zinus F2 Karawang',
            'Zinus F3 Tangerang',
        ])->merge(
            Asset::query()
                ->whereNotNull('factory')
                ->where('factory', '!=', '')
                ->distinct()
                ->orderBy('factory')
                ->pluck('factory')
        )->unique()->values();

        $filterCategories = Asset::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        $filterDepartments = Department::orderBy('name')->get();

        // Recent activity
        $recentLogs = AssetLog::with(['asset', 'actor'])
            ->latest()
            ->take(5)
            ->get();

        // Recently attached relations
        $recentlyAttached = AssetRelation::with(['parentAsset', 'childAsset'])
            ->whereNull('ended_at')
            ->latest()
            ->take(5)
            ->get();

        return view('assets.overview', compact(
            'totalPc',
            'totalLaptop',
            'totalMonitor',
            'totalPrinterScanner',
            'totalNetwork',
            'totalCctv',
            'totalPeripheral',
            'totalLicense',
            'recentLogs',
            'recentlyAttached',
            'assets',
            'filters',
            'perPage',
            'filterFactories',
            'filterCategories',
            'filterDepartments'
        ));
    }

    public function manualIndex(Request $request)
    {
        $filters = [
            'factory' => $request->query('factory'),
            'department' => $request->integer('department') ?: null,
            'category' => $request->query('category'),
            'status' => $request->query('status'),
            'search' => $request->query('search'),
        ];
        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;

        $base = Asset::query()->where('source_type', 'manual');

        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'in_use')->count(),
            'in_repair' => (clone $base)->where('status', 'maintenance')->count(),
            'spare' => (clone $base)->where('status', 'available')->count(),
            'retired' => (clone $base)->where('status', 'broken')->count(),
        ];

        // Custom search and filters for manual assets
        $query = Asset::query()->where('source_type', 'manual');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('asset_code', 'LIKE', "%{$search}%")
                  ->orWhere('serial_number', 'LIKE', "%{$search}%")
                  ->orWhere('brand', 'LIKE', "%{$search}%")
                  ->orWhere('model', 'LIKE', "%{$search}%")
                  ->orWhere('location', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['factory'])) {
            $query->where('factory', $filters['factory']);
        }

        if (!empty($filters['department'])) {
            $query->where('department_id', $filters['department']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['status'])) {
            $statusMap = [
                'active' => 'in_use',
                'in_repair' => 'maintenance',
                'spare' => 'available',
                'retired' => 'broken',
            ];
            $dbStatus = $statusMap[$filters['status']] ?? $filters['status'];
            $query->where('status', $dbStatus);
        }

        $assets = $query->with(['department', 'user'])->paginate($perPage)->withQueryString();

        // Get unique categories of manual assets for filters
        $manualCategories = Asset::where('source_type', 'manual')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->toArray();

        // If manualCategories is empty, provide standard fallback categories
        if (empty($manualCategories)) {
            $manualCategories = [
                'Printer', 'Scanner', 'Monitor', 'CCTV', 'NVR/DVR',
                'Router', 'Switch', 'Access Point', 'Keyboard',
                'Mouse', 'UPS', 'Projector', 'Other IT Equipment'
            ];
        }

        return view('assets.manual.index', [
            'assets' => $assets,
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
                'categories' => $manualCategories,
                'statuses' => ['active', 'in_repair', 'spare', 'retired'],
            ]
        ]);
    }

    public function manualCreate(Request $request)
    {
        $departments = Department::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        $categories = [
            'PC', 'Laptop', 'Monitor', 'Printer & Scanner', 'Printer', 'Scanner',
            'Network Device', 'Router', 'Switch', 'Access Point', 'CCTV', 'NVR/DVR',
            'Peripheral', 'Keyboard', 'Mouse', 'UPS', 'Projector',
            'Software License', 'License', 'Other IT Equipment'
        ];

        return view('assets.manual.create', compact('departments', 'users', 'categories'));
    }

    public function manualStore(StoreAssetRequest $request)
    {
        $data = $request->validated();

        // Auto-generate asset code if not supplied
        if (empty($data['asset_code'])) {
            $data['asset_code'] = 'AST-MAN-' . strtoupper(Str::random(8));
        }

        $data['source_type'] = 'manual';
        $data['sync_source'] = 'manual';

        $this->assetService->store($data, Auth::user());

        // Redirect to category index page if possible
        $cat = strtolower($data['category']);
        if (str_contains($cat, 'pc')) {
            return redirect()->route('admin.assets.pc')->with('success', 'Manual asset created successfully.');
        } elseif (str_contains($cat, 'laptop')) {
            return redirect()->route('admin.assets.laptop')->with('success', 'Manual asset created successfully.');
        } elseif (str_contains($cat, 'monitor')) {
            return redirect()->route('admin.assets.monitor')->with('success', 'Manual asset created successfully.');
        } elseif (str_contains($cat, 'printer') || str_contains($cat, 'scanner')) {
            return redirect()->route('admin.assets.printer-scanner')->with('success', 'Manual asset created successfully.');
        } elseif (str_contains($cat, 'network') || str_contains($cat, 'router') || str_contains($cat, 'switch') || str_contains($cat, 'access')) {
            return redirect()->route('admin.assets.network-device')->with('success', 'Manual asset created successfully.');
        } elseif (str_contains($cat, 'cctv') || str_contains($cat, 'nvr') || str_contains($cat, 'dvr')) {
            return redirect()->route('admin.assets.cctv')->with('success', 'Manual asset created successfully.');
        } elseif (str_contains($cat, 'peripheral') || str_contains($cat, 'keyboard') || str_contains($cat, 'mouse') || str_contains($cat, 'ups') || str_contains($cat, 'projector')) {
            return redirect()->route('admin.assets.peripheral')->with('success', 'Manual asset created successfully.');
        } elseif (str_contains($cat, 'license') || str_contains($cat, 'software')) {
            return redirect()->route('admin.assets.software-license')->with('success', 'Manual asset created successfully.');
        }

        return redirect()->route('admin.assets.manual.index')
            ->with('success', 'Manual asset created successfully.');
    }

    public function manualEdit(Asset $asset)
    {
        if ($asset->source_type !== 'manual') {
            return redirect()->route('admin.assets.manual.index')
                ->with('error', 'Selected asset is not a manually managed asset.');
        }

        $departments = Department::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        $categories = [
            'PC', 'Laptop', 'Monitor', 'Printer & Scanner', 'Printer', 'Scanner',
            'Network Device', 'Router', 'Switch', 'Access Point', 'CCTV', 'NVR/DVR',
            'Peripheral', 'Keyboard', 'Mouse', 'UPS', 'Projector',
            'Software License', 'License', 'Other IT Equipment'
        ];

        return view('assets.manual.edit', compact('asset', 'departments', 'users', 'categories'));
    }

    public function manualUpdate(UpdateAssetRequest $request, Asset $asset)
    {
        if ($asset->source_type !== 'manual') {
            return redirect()->route('admin.assets.manual.index')
                ->with('error', 'Selected asset is not a manually managed asset.');
        }

        $data = $request->validated();
        $data['source_type'] = 'manual';
        $data['sync_source'] = 'manual';

        $this->assetService->update($asset, $data, Auth::user());

        return redirect()->route('admin.assets.manual.edit', $asset)
            ->with('success', 'Manual asset updated successfully.');
    }

    public function manualDestroy(Asset $asset)
    {
        if ($asset->source_type !== 'manual') {
            return redirect()->route('admin.assets.manual.index')
                ->with('error', 'Selected asset is not a manually managed asset.');
        }

        $this->assetService->delete($asset, Auth::user());

        return redirect()->route('admin.assets.manual.index')
            ->with('success', 'Manual asset deleted successfully.');
    }

    public function assignment(Request $request)
    {
        $search = $request->query('search');

        // Borrow Logs (Peminjaman)
        $borrowQuery = BorrowLog::with(['user', 'department', 'asset', 'processedBy'])
            ->latest();

        if ($search) {
            $borrowQuery->where(function ($q) use ($search) {
                $q->where('asset_code', 'LIKE', "%{$search}%")
                  ->orWhere('reason', 'LIKE', "%{$search}%")
                  ->orWhere('status', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($sub) use ($search) {
                      $sub->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('asset', function ($sub) use ($search) {
                      $sub->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('serial_number', 'LIKE', "%{$search}%");
                  });
            });
        }

        $borrowLogs = $borrowQuery->paginate(10, ['*'], 'borrow_page')->withQueryString();

        // Audit Trail Logs (Asset changes)
        $auditQuery = AssetLog::with(['asset', 'actor'])
            ->latest();

        if ($search) {
            $auditQuery->where(function ($q) use ($search) {
                $q->where('action', 'LIKE', "%{$search}%")
                  ->orWhere('notes', 'LIKE', "%{$search}%")
                  ->orWhereHas('asset', function ($sub) use ($search) {
                      $sub->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('asset_code', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('actor', function ($sub) use ($search) {
                      $sub->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $auditLogs = $auditQuery->paginate(10, ['*'], 'audit_page')->withQueryString();

        return view('assets.assignment', compact('borrowLogs', 'auditLogs', 'search'));
    }

    public function auditLog(Request $request)
    {
        $search = $request->query('search');
        $action = $request->query('action');

        $query = AssetLog::with(['asset', 'actor'])->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'LIKE', "%{$search}%")
                  ->orWhere('notes', 'LIKE', "%{$search}%")
                  ->orWhereHas('asset', function ($sub) use ($search) {
                      $sub->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('asset_code', 'LIKE', "%{$search}%")
                          ->orWhere('serial_number', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('actor', function ($sub) use ($search) {
                      $sub->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($action) {
            $query->where('action', $action);
        }

        $auditLogs = $query->paginate(15)->withQueryString();
        $actions = AssetLog::select('action')->distinct()->pluck('action')->filter()->values();

        return view('assets.audit-log', compact('auditLogs', 'actions', 'search', 'action'));
    }

    public function updateLifecycle(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'lifecycle_status' => 'required|string|in:active,in_repair,spare,assigned,disposed,lost,replaced',
            'condition' => 'required|string|in:good,minor_issue,damaged,repair,disposed,lost',
            'warranty_until' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Map lifecycle status to standard main status where appropriate
        $statusMap = [
            'active' => 'in_use',
            'in_repair' => 'maintenance',
            'spare' => 'available',
            'assigned' => 'in_use',
            'disposed' => 'broken',
            'lost' => 'broken',
            'replaced' => 'broken',
        ];

        $data['status'] = $statusMap[$data['lifecycle_status']] ?? $asset->status;

        $this->assetService->update($asset, $data, Auth::user());

        return redirect()->back()->with('success', 'Asset lifecycle updated successfully.');
    }

    public function importExport()
    {
        return view('assets.import-export');
    }

    public function export()
    {
        $csvFileName = 'assets_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Asset Code', 'Name', 'Hostname', 'Category', 'Sub Category', 'Factory', 'Brand', 'Model', 'Serial Number', 'IP Address', 'CPU', 'RAM GB', 'Storage GB', 'OS Name', 'RustDesk ID', 'Specs', 'Source Type', 'Condition', 'Lifecycle Status', 'Warranty Until', 'Location', 'Status', 'Notes'];

        $callback = function() use($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            Asset::query()->lazy(250)->each(function ($asset) use ($file) {
                fputcsv($file, [
                    $asset->asset_code,
                    $asset->name,
                    $asset->hostname,
                    $asset->category,
                    $asset->sub_category,
                    $asset->factory,
                    $asset->brand,
                    $asset->model,
                    $asset->serial_number,
                    $asset->ip_address,
                    $asset->cpu,
                    $asset->ram_gb,
                    $asset->storage_gb,
                    $asset->os_name,
                    $asset->rustdesk_id,
                    $asset->specs,
                    $asset->source_type,
                    $asset->condition ?: 'good',
                    $asset->lifecycle_status ?: 'active',
                    $asset->warranty_until?->format('Y-m-d'),
                    $asset->location,
                    $asset->status,
                    $asset->notes,
                ]);
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $data = array_map('str_getcsv', file($path));
        if (count($data) < 2) {
            return back()->with('error', 'CSV file is empty or invalid.');
        }

        $header = array_shift($data);
        // Normalize headers
        $header = array_map(function($h) {
            return strtolower(trim(str_replace(' ', '_', $h)));
        }, $header);

        $imported = 0;
        $errors = [];

        foreach ($data as $rowIndex => $row) {
            if (count($row) !== count($header)) {
                continue;
            }
            $row = array_combine($header, $row);

            $assetCode = $this->csvValue($row, 'asset_code');
            if ($assetCode === null) {
                $assetCode = 'AST-IMP-' . strtoupper(Str::random(8));
            }

            $serialNumber = $this->csvValue($row, 'serial_number');
            $hostname = $this->csvValue($row, 'hostname');

            try {
                if ($serialNumber !== null) {
                    $duplicateSerial = Asset::withTrashed()
                        ->where('serial_number', $serialNumber)
                        ->where('asset_code', '!=', $assetCode)
                        ->exists();
                    if ($duplicateSerial) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Serial number '{$serialNumber}' is already in use.";
                        continue;
                    }
                }

                if ($hostname !== null) {
                    $duplicateHostname = Asset::withTrashed()
                        ->where('hostname', $hostname)
                        ->where('asset_code', '!=', $assetCode)
                        ->exists();
                    if ($duplicateHostname) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Hostname '{$hostname}' is already in use.";
                        continue;
                    }
                }

                $sourceType = $this->normalizeImportedSourceType($this->csvValue($row, 'source_type'));
                $payload = [
                    'asset_code' => $assetCode,
                    'name' => $this->csvValue($row, 'name', $hostname ?: 'Imported Asset'),
                    'hostname' => $hostname,
                    'category' => $this->csvValue($row, 'category', 'Other IT Equipment'),
                    'sub_category' => $this->csvValue($row, 'sub_category'),
                    'factory' => $this->csvValue($row, 'factory'),
                    'brand' => $this->csvValue($row, 'brand'),
                    'model' => $this->csvValue($row, 'model'),
                    'serial_number' => $serialNumber,
                    'ip_address' => $this->csvValue($row, 'ip_address'),
                    'cpu' => $this->csvValue($row, 'cpu'),
                    'ram_gb' => $this->csvValue($row, 'ram_gb'),
                    'storage_gb' => $this->csvValue($row, 'storage_gb'),
                    'os_name' => $this->csvValue($row, 'os_name'),
                    'rustdesk_id' => $this->csvValue($row, 'rustdesk_id'),
                    'specs' => $this->csvValue($row, 'specs'),
                    'source_type' => $sourceType,
                    'sync_source' => $sourceType === 'agent' ? 'agent' : 'manual',
                    'condition' => $this->normalizeImportedCondition($this->csvValue($row, 'condition')),
                    'lifecycle_status' => $this->normalizeImportedLifecycleStatus($this->csvValue($row, 'lifecycle_status')),
                    'warranty_until' => $this->csvValue($row, 'warranty_until') ? Carbon::parse($this->csvValue($row, 'warranty_until')) : null,
                    'location' => $this->csvValue($row, 'location'),
                    'status' => $this->normalizeImportedStatus($this->csvValue($row, 'status')),
                    'notes' => $this->csvValue($row, 'notes', 'Imported via CSV.'),
                ];

                $existingAsset = Asset::withTrashed()
                    ->where('asset_code', $assetCode)
                    ->first();

                if ($existingAsset?->trashed()) {
                    $existingAsset->restore();
                }

                if ($existingAsset) {
                    $this->assetService->update($existingAsset->fresh(), $payload, Auth::user());
                } else {
                    $this->assetService->store($payload, Auth::user());
                }

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
            }
        }

        if (count($errors) > 0) {
            return back()->with('success', "Imported {$imported} assets. Warnings: " . implode(', ', $errors));
        }

        return back()->with('success', "Successfully imported {$imported} assets from CSV!");
    }

    public function pcIndex(Request $request)
    {
        return $this->categoryIndexView($request, 'PC', ['PC', 'PC / Laptop', 'PC/Laptop']);
    }

    public function laptopIndex(Request $request)
    {
        return $this->categoryIndexView($request, 'Laptop', ['Laptop', 'pc-laptop']);
    }

    public function monitorIndex(Request $request)
    {
        return $this->categoryIndexView($request, 'Monitor', ['Monitor']);
    }

    public function printerScannerIndex(Request $request)
    {
        return $this->categoryIndexView($request, 'Printer & Scanner', ['Printer & Scanner', 'Printer', 'Scanner', 'printer-scanner']);
    }

    public function networkDeviceIndex(Request $request)
    {
        return $this->categoryIndexView($request, 'Network Device', ['Network Device', 'Router', 'Switch', 'Access Point', 'network-device']);
    }

    public function cctvIndex(Request $request)
    {
        return $this->categoryIndexView($request, 'CCTV', ['CCTV', 'NVR/DVR', 'cctv']);
    }

    public function peripheralIndex(Request $request)
    {
        return $this->categoryIndexView($request, 'Peripheral', ['Peripheral', 'Keyboard', 'Mouse', 'UPS', 'Projector', 'peripheral']);
    }

    public function softwareLicenseIndex(Request $request)
    {
        return $this->categoryIndexView($request, 'Software License', ['Software License', 'License', 'software-license']);
    }

    protected function categoryIndexView(Request $request, string $title, array $categories)
    {
        $search = $request->query('search');
        $factory = $request->query('factory');
        $departmentId = $request->integer('department') ?: null;
        $location = $request->query('location');
        $status = $request->query('status');
        $lifecycleStatus = $request->query('lifecycle_status');
        $brand = $request->query('brand');

        $query = Asset::query()
            ->with(['department', 'user'])
            ->where(function ($q) use ($categories) {
                foreach ($categories as $cat) {
                    $q->orWhere('category', $cat)
                      ->orWhereRaw('LOWER(category) = ?', [strtolower($cat)]);
                }
            });

        // Search & Filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('hostname', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('specs', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($factory) {
            $query->where('factory', $factory);
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($location) {
            $query->where('location', 'like', "%{$location}%");
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($lifecycleStatus) {
            $query->where('lifecycle_status', $lifecycleStatus);
        }

        if ($brand) {
            $query->where('brand', 'like', "%{$brand}%");
        }

        $assets = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        // Get filter options
        $factoriesList = Asset::whereNotNull('factory')->where('factory', '!=', '')->distinct()->pluck('factory');
        $departmentsList = Department::orderBy('name')->get();
        $brandsList = Asset::whereNotNull('brand')->where('brand', '!=', '')->distinct()->pluck('brand');

        return view('assets.category-index', compact(
            'assets',
            'title',
            'categories',
            'factoriesList',
            'departmentsList',
            'brandsList',
            'search',
            'factory',
            'departmentId',
            'location',
            'status',
            'lifecycleStatus',
            'brand'
        ));
    }

    public function showDetail(Asset $asset)
    {
        $asset->load(['department', 'user']);

        // Check active parent PC if this is a child
        $activeParentRelation = $asset->activeParentRelation()->first();
        $parentAsset = $activeParentRelation ? $activeParentRelation->parentAsset : null;

        // Check active attached child assets if this is a parent PC/Laptop
        $attachedAssets = $asset->attachedAssets;

        // All relation history (ended and active)
        $relationHistory = AssetRelation::with(['parentAsset', 'childAsset', 'creator'])
            ->where(function ($q) use ($asset) {
                $q->where('parent_asset_id', $asset->id)
                  ->orWhere('child_asset_id', $asset->id);
            })
            ->orderByDesc('created_at')
            ->get();

        // Get list of attachable assets if this is a PC/Laptop
        $isParentCategory = $this->isParentAsset($asset);
        $attachableAssets = collect();
        $attachableParents = collect();
        if ($isParentCategory) {
            // Attachable assets are those that:
            // 1. Are not PC/Laptop
            // 2. Are not already active child in another relation
            $attachableAssets = Asset::whereKeyNot($asset->id)
                ->whereNotIn('category', ['PC', 'Laptop', 'PC / Laptop', 'PC/Laptop', 'pc-laptop'])
                ->whereDoesntHave('activeParentRelation')
                ->orderBy('name')
                ->get()
                ->reject(fn (Asset $candidate) => $this->isParentAsset($candidate))
                ->values();
        } else if (!$parentAsset) {
            // Attachable parents are those that:
            // 1. Are PC/Laptop category
            $attachableParents = Asset::whereIn('category', ['PC', 'Laptop', 'PC / Laptop', 'PC/Laptop', 'pc-laptop'])
                ->orderBy('name')
                ->get()
                ->filter(fn (Asset $candidate) => $this->isParentAsset($candidate))
                ->values();
        }

        // Get logs / mutation history
        $mutationHistory = AssetLog::with('actor')
            ->where('asset_id', $asset->id)
            ->latest()
            ->get();

        $mutationUserIds = $mutationHistory
            ->flatMap(function (AssetLog $log) {
                $changes = $log->metadata['changes'] ?? [];
                $previous = $log->metadata['previous'] ?? [];

                return [
                    $changes['user_id'] ?? null,
                    $previous['user_id'] ?? null,
                ];
            })
            ->filter()
            ->unique()
            ->values();

        $mutationDepartmentIds = $mutationHistory
            ->flatMap(function (AssetLog $log) {
                $changes = $log->metadata['changes'] ?? [];
                $previous = $log->metadata['previous'] ?? [];

                return [
                    $changes['department_id'] ?? null,
                    $previous['department_id'] ?? null,
                ];
            })
            ->filter()
            ->unique()
            ->values();

        $mutationUsers = User::whereKey($mutationUserIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $mutationDepartments = Department::whereKey($mutationDepartmentIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        return view('assets.detail', compact(
            'asset',
            'activeParentRelation',
            'parentAsset',
            'attachedAssets',
            'relationHistory',
            'attachableAssets',
            'attachableParents',
            'mutationHistory',
            'mutationUsers',
            'mutationDepartments',
            'isParentCategory'
        ));
    }

    public function attachRelation(Request $request, Asset $asset)
    {
        $request->validate([
            'child_asset_id' => 'required|exists:assets,id',
            'notes' => 'nullable|string|max:5000',
        ]);

        $childId = $request->integer('child_asset_id');

        if ($asset->id === $childId) {
            return back()->with('error', 'Parent and child asset cannot be the same.');
        }

        try {
            $message = $this->attachAssets($asset->id, $childId, $request->input('notes'));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }

    public function attachParentRelation(Request $request, Asset $asset)
    {
        $request->validate([
            'parent_asset_id' => 'required|exists:assets,id',
            'notes' => 'nullable|string|max:5000',
        ]);

        $parentId = $request->integer('parent_asset_id');

        if ($asset->id === $parentId) {
            return back()->with('error', 'Parent and child asset cannot be the same.');
        }

        try {
            $message = $this->attachAssets($parentId, $asset->id, $request->input('notes'));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }

    public function detachRelation(Request $request, $relationId)
    {
        try {
            $message = DB::transaction(function () use ($relationId) {
                $relation = AssetRelation::whereKey($relationId)
                    ->lockForUpdate()
                    ->first();

                if (! $relation) {
                    throw new InvalidArgumentException('Asset relation not found.');
                }

                if ($relation->ended_at) {
                    return 'Asset relation was already detached.';
                }

                $parent = Asset::withTrashed()->find($relation->parent_asset_id);
                $child = Asset::withTrashed()->find($relation->child_asset_id);

                $this->endRelation(
                    $relation,
                    'Detached by ' . (Auth::user()->name ?? 'Admin') . ' on ' . now()->toDateTimeString()
                );

                if ($parent) {
                    $parent->assetLogs()->create([
                        'actor_id' => Auth::id(),
                        'action' => 'relation_detached',
                        'notes' => 'Detached child asset: ' . $this->assetLabel($child, $relation->child_asset_id),
                    ]);
                }

                if ($child) {
                    $child->assetLogs()->create([
                        'actor_id' => Auth::id(),
                        'action' => 'relation_detached',
                        'notes' => 'Detached from parent PC: ' . $this->assetLabel($parent, $relation->parent_asset_id),
                    ]);
                }

                return 'Asset successfully detached.';
            });
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }

    private function attachAssets(int $parentId, int $childId, ?string $notes): string
    {
        return DB::transaction(function () use ($parentId, $childId, $notes) {
            $parent = Asset::whereKey($parentId)
                ->lockForUpdate()
                ->first();
            $child = Asset::whereKey($childId)
                ->lockForUpdate()
                ->first();

            if (! $parent || ! $child) {
                throw new InvalidArgumentException('Selected asset was not found.');
            }

            if ($parent->id === $child->id) {
                throw new InvalidArgumentException('Parent and child asset cannot be the same.');
            }

            if (! $this->isParentAsset($parent)) {
                throw new InvalidArgumentException('Only PC or Laptop assets can be used as parent assets.');
            }

            if ($this->isParentAsset($child)) {
                throw new InvalidArgumentException('PC or Laptop assets cannot be attached as child assets.');
            }

            $existing = AssetRelation::active()
                ->where('child_asset_id', $child->id)
                ->lockForUpdate()
                ->first();

            if ($existing && (int) $existing->parent_asset_id === (int) $parent->id) {
                return 'Asset is already attached to this parent.';
            }

            if ($existing) {
                $this->endRelation($existing, 'Detached automatically due to re-assignment.');
            }

            AssetRelation::create([
                'parent_asset_id' => $parent->id,
                'child_asset_id' => $child->id,
                'relation_type' => AssetRelation::TYPE_ATTACHED,
                'started_at' => now(),
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);

            $parent->assetLogs()->create([
                'actor_id' => Auth::id(),
                'action' => 'relation_attached',
                'notes' => 'Attached child asset: ' . $this->assetLabel($child),
            ]);

            $child->assetLogs()->create([
                'actor_id' => Auth::id(),
                'action' => 'relation_attached',
                'notes' => 'Attached to parent PC: ' . $this->assetLabel($parent),
            ]);

            return 'Asset successfully attached.';
        });
    }

    private function endRelation(AssetRelation $relation, string $note): void
    {
        $relation->update([
            'ended_at' => now(),
            'notes' => trim(((string) $relation->notes) . "\n" . $note),
        ]);
    }

    private function isParentAsset(Asset $asset): bool
    {
        return in_array($this->normalizedCategory($asset->category), [
            'pc',
            'laptop',
            'pc / laptop',
            'pc/laptop',
            'pc laptop',
        ], true);
    }

    private function normalizedCategory(?string $category): string
    {
        $normalized = strtolower(trim((string) $category));
        $normalized = str_replace(['_', '-'], ' ', $normalized);

        return preg_replace('/\s+/', ' ', $normalized) ?: '';
    }

    private function assetLabel(?Asset $asset, ?int $fallbackId = null): string
    {
        if (! $asset) {
            return 'Asset #' . ($fallbackId ?: 'unknown');
        }

        return trim($asset->name . ' [' . $asset->asset_code . ']');
    }

    private function csvValue(array $row, string $key, ?string $default = null): ?string
    {
        $value = isset($row[$key]) ? trim((string) $row[$key]) : '';

        return $value !== '' ? $value : $default;
    }

    private function normalizeImportedStatus(?string $status): string
    {
        $normalized = Str::snake(Str::lower((string) $status));
        $map = [
            'active' => Asset::STATUS_IN_USE,
            'in_use' => Asset::STATUS_IN_USE,
            'in_repair' => Asset::STATUS_MAINTENANCE,
            'maintenance' => Asset::STATUS_MAINTENANCE,
            'spare' => Asset::STATUS_AVAILABLE,
            'available' => Asset::STATUS_AVAILABLE,
            'retired' => Asset::STATUS_BROKEN,
            'broken' => Asset::STATUS_BROKEN,
        ];

        if (in_array($normalized, Asset::STATUSES, true)) {
            return $normalized;
        }

        return $map[$normalized] ?? Asset::STATUS_AVAILABLE;
    }

    private function normalizeImportedCondition(?string $condition): string
    {
        $normalized = Str::snake(Str::lower((string) $condition));

        return in_array($normalized, ['good', 'minor_issue', 'damaged', 'repair', 'disposed', 'lost'], true)
            ? $normalized
            : 'good';
    }

    private function normalizeImportedLifecycleStatus(?string $status): string
    {
        $normalized = Str::snake(Str::lower((string) $status));

        return in_array($normalized, ['active', 'in_repair', 'spare', 'assigned', 'disposed', 'lost', 'replaced'], true)
            ? $normalized
            : 'active';
    }

    private function normalizeImportedSourceType(?string $sourceType): string
    {
        $normalized = Str::snake(Str::lower((string) $sourceType));

        return in_array($normalized, ['agent', 'manual', 'import_excel'], true)
            ? $normalized
            : 'import_excel';
    }
}
