<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RemoteSystemController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $connection = $request->query('connection', 'all');
        if (! in_array($connection, ['all', 'ready', 'missing'], true)) {
            $connection = 'all';
        }

        $baseQuery = Asset::query()->remoteEndpoints();
        $totalEndpoints = (clone $baseQuery)->count();
        $readyEndpoints = (clone $baseQuery)
            ->whereNotNull('anydesk_id')
            ->where('anydesk_id', '!=', '')
            ->count();

        $assets = $baseQuery
            ->with(['user', 'department'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('hostname', 'like', "%{$search}%")
                        ->orWhere('asset_code', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('anydesk_id', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('department', fn (Builder $departmentQuery) => $departmentQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($connection === 'ready', function (Builder $query) {
                $query->whereNotNull('anydesk_id')
                    ->where('anydesk_id', '!=', '');
            })
            ->when($connection === 'missing', function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->whereNull('anydesk_id')
                        ->orWhere('anydesk_id', '');
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('remote-system.index', [
            'assets' => $assets,
            'search' => $search,
            'connection' => $connection,
            'totalEndpoints' => $totalEndpoints,
            'readyEndpoints' => $readyEndpoints,
            'missingEndpoints' => $totalEndpoints - $readyEndpoints,
        ]);
    }

    public function ping(Request $request): JsonResponse
    {
        $ip = $request->query('ip');
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return response()->json(['status' => 'offline']);
        }

        exec(sprintf('ping -c 1 -W 1 %s', escapeshellarg($ip)), $output, $result);

        return response()->json(['status' => $result === 0 ? 'online' : 'offline']);
    }
}
