<?php

namespace App\Http\Controllers;

use App\Services\AssetAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetAutomationController extends Controller
{
    public function __construct(private AssetAutomationService $automation)
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(): View
    {
        return view('assets.automation-console', [
            'commands' => $this->automation->commands(),
            'environment' => $this->automation->environment(),
        ]);
    }

    public function run(Request $request): JsonResponse
    {
        $data = $request->validate([
            'command_key' => ['required', 'string', 'max:80'],
            'segments' => ['nullable', 'string', 'max:5000'],
            'targets' => ['nullable', 'string', 'max:10000'],
            'start_host' => ['nullable', 'integer', 'min:1', 'max:254'],
            'end_host' => ['nullable', 'integer', 'min:1', 'max:254'],
            'max_parallel' => ['nullable', 'integer', 'min:1', 'max:50'],
            'factory' => ['nullable', 'string', 'max:150'],
            'department' => ['nullable', 'string', 'max:150'],
            'server_url' => ['nullable', 'url', 'max:500'],
            'token' => ['nullable', 'string', 'max:1000'],
            'use_config_token' => ['nullable', 'boolean'],
            'probe_wsman' => ['nullable', 'boolean'],
            'probe_ping' => ['nullable', 'boolean'],
            'probe_ssh' => ['nullable', 'boolean'],
            'probe_winrm' => ['nullable', 'boolean'],
            'probe_rdp' => ['nullable', 'boolean'],
            'probe_smb' => ['nullable', 'boolean'],
            'skip_bootstrap' => ['nullable', 'boolean'],
            'skip_anydesk_collect' => ['nullable', 'boolean'],
            'skip_remote_scan' => ['nullable', 'boolean'],
            'skip_network_devices' => ['nullable', 'boolean'],
            'skip_local_printers' => ['nullable', 'boolean'],
            'skip_existing' => ['nullable', 'boolean'],
            'skip_preflight' => ['nullable', 'boolean'],
            'no_fail_exit' => ['nullable', 'boolean'],
            'dry_run' => ['nullable', 'boolean'],
            'include_network_printers' => ['nullable', 'boolean'],
            'include_other_local_ports' => ['nullable', 'boolean'],
            'skip_snmp' => ['nullable', 'boolean'],
            'include_gateways' => ['nullable', 'boolean'],
        ]);

        $result = $this->automation->run($data['command_key'], $data, $request->user());

        return response()->json($result);
    }
}
