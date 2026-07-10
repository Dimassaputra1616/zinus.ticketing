<?php

namespace App\Support;

use App\Models\Asset;
use Illuminate\Http\Request;

final class AssetModuleNavigation
{
    public static function safeReturnUrl(Request $request, string $field = 'redirect_to'): ?string
    {
        $url = trim((string) $request->input($field, ''));

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $target = parse_url($url);

        if (! is_array($target) || empty($target['host'])) {
            return null;
        }

        $targetOrigin = ($target['scheme'] ?? $request->getScheme()) . '://' . $target['host'];
        if (isset($target['port'])) {
            $targetOrigin .= ':' . $target['port'];
        }

        return hash_equals($request->getSchemeAndHttpHost(), $targetOrigin) ? $url : null;
    }

    public static function routeForAsset(Asset $asset): string
    {
        $routeName = match (AssetCategoryProfile::key($asset->category)) {
            'pc' => 'admin.assets.pc',
            'laptop' => 'admin.assets.laptop',
            'monitor' => 'admin.assets.monitor',
            'printer' => 'admin.assets.printer-scanner',
            'network' => 'admin.assets.network-device',
            'cctv' => 'admin.assets.cctv',
            'peripheral' => 'admin.assets.peripheral',
            'software' => 'admin.assets.software-license',
            default => $asset->source_type === 'manual' ? 'admin.assets.manual.index' : 'assets.index',
        };

        return route($routeName);
    }
}
