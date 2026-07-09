<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'group' => 'general',
                'key' => 'app_name',
                'value' => 'Asset Management',
                'type' => 'string',
                'description' => 'The name of the application displayed in the header',
            ],
            [
                'group' => 'general',
                'key' => 'app_logo',
                'value' => null, // Path to uploaded logo
                'type' => 'image',
                'description' => 'Brand logo of the company',
            ],
            [
                'group' => 'theme',
                'key' => 'theme_color',
                'value' => '#12824C',
                'type' => 'string',
                'description' => 'Primary theme color (Zinus Green)',
            ],
            [
                'group' => 'theme',
                'key' => 'theme_color_strong',
                'value' => '#0F6D3F',
                'type' => 'string',
                'description' => 'Primary hover/strong color (Strong Green)',
            ],
            [
                'group' => 'theme',
                'key' => 'theme_color_secondary',
                'value' => '#53B77A',
                'type' => 'string',
                'description' => 'Secondary accent color (Mint)',
            ],
            [
                'group' => 'theme',
                'key' => 'sidebar_color',
                'value' => '#0E1F1B',
                'type' => 'string',
                'description' => 'Sidebar background color',
            ],
            [
                'group' => 'theme',
                'key' => 'sidebar_text_color',
                'value' => '#ffffff',
                'type' => 'string',
                'description' => 'Sidebar text and icon color',
            ],
            [
                'group' => 'theme',
                'key' => 'welcome_message',
                'value' => 'Welcome to IT Support Dashboard',
                'type' => 'text',
                'description' => 'Greeting message shown on the dashboard',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
