<?php

return [
    'enabled' => env('ASSET_AUTOMATION_CONSOLE_ENABLED', true),

    'installer_path' => env('ASSET_AUTOMATION_INSTALLER_PATH', base_path('ZinusAssetInstaller')),

    'powershell_path' => env('ASSET_AUTOMATION_POWERSHELL_PATH'),

    'timeout_seconds' => (int) env('ASSET_AUTOMATION_TIMEOUT_SECONDS', 180),
];
