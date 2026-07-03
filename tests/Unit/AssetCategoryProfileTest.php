<?php

namespace Tests\Unit;

use App\Support\AssetCategoryProfile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AssetCategoryProfileTest extends TestCase
{
    #[DataProvider('categoryProvider')]
    public function test_category_aliases_resolve_to_the_expected_profile(string $category, string $expected): void
    {
        $this->assertSame($expected, AssetCategoryProfile::key($category));
    }

    public static function categoryProvider(): array
    {
        return [
            ['PC', 'pc'],
            ['Laptop', 'laptop'],
            ['Monitor', 'monitor'],
            ['Printer & Scanner', 'printer'],
            ['Scanner', 'printer'],
            ['Network Device', 'network'],
            ['Access Point', 'network'],
            ['CCTV', 'cctv'],
            ['NVR/DVR', 'cctv'],
            ['Peripheral', 'peripheral'],
            ['UPS', 'peripheral'],
            ['Software License', 'software'],
            ['Other IT Equipment', 'other'],
        ];
    }
}
