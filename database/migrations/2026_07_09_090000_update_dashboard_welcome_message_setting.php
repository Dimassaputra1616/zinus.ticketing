<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $targetMessage = 'Welcome to IT Support Dashboard';
        $legacyMessages = [
            'Welcome to out Management Dashboard',
            'Welcome to our Management Dashboard',
            'Welcome to the Zinus Dream Dashboard',
            'Welcome to the Zinus Ticketing Dashboard',
        ];

        $setting = DB::table('settings')->where('key', 'welcome_message')->first();

        if (! $setting) {
            DB::table('settings')->insert([
                'group' => 'theme',
                'key' => 'welcome_message',
                'value' => $targetMessage,
                'type' => 'text',
                'description' => 'Greeting message shown on the dashboard',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } elseif (in_array($setting->value, $legacyMessages, true)) {
            DB::table('settings')
                ->where('key', 'welcome_message')
                ->update([
                    'group' => 'theme',
                    'value' => $targetMessage,
                    'type' => 'text',
                    'description' => $setting->description ?: 'Greeting message shown on the dashboard',
                    'updated_at' => now(),
                ]);
        }

        Cache::forget('setting_welcome_message');
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('key', 'welcome_message')
            ->where('value', 'Welcome to IT Support Dashboard')
            ->update([
                'value' => 'Welcome to the Zinus Dream Dashboard',
                'updated_at' => now(),
            ]);

        Cache::forget('setting_welcome_message');
    }
};
