<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public $app_name;
    public $theme_color;
    public $theme_color_strong;
    public $theme_color_secondary;
    public $sidebar_color;
    public $sidebar_text_color;
    public $welcome_message;
    public $app_logo;

    public function mount()
    {
        $this->app_name = setting('app_name', 'Zinus Dream');
        $this->theme_color = setting('theme_color', '#12824C');
        $this->theme_color_strong = setting('theme_color_strong', '#0F6D3F');
        $this->theme_color_secondary = setting('theme_color_secondary', '#53B77A');
        $this->sidebar_color = setting('sidebar_color', '#0E1F1B');
        $this->sidebar_text_color = setting('sidebar_text_color', '#ffffff');
        $this->welcome_message = setting('welcome_message', 'Welcome to out Management Dashboard');
    }

    public function save()
    {
        $this->validate([
            'app_name' => 'required|string|max:255',
            'theme_color' => 'required|string|max:50',
            'theme_color_strong' => 'required|string|max:50',
            'theme_color_secondary' => 'required|string|max:50',
            'sidebar_color' => 'required|string|max:50',
            'sidebar_text_color' => 'required|string|max:50',
            'welcome_message' => 'required|string',
            'app_logo' => 'nullable|image|max:2048', // max 2MB
        ]);

        Setting::updateOrCreate(['key' => 'app_name'], ['group' => 'general', 'value' => $this->app_name, 'type' => 'string']);
        Setting::updateOrCreate(['key' => 'theme_color'], ['group' => 'theme', 'value' => $this->theme_color, 'type' => 'string']);
        Setting::updateOrCreate(['key' => 'theme_color_strong'], ['group' => 'theme', 'value' => $this->theme_color_strong, 'type' => 'string']);
        Setting::updateOrCreate(['key' => 'theme_color_secondary'], ['group' => 'theme', 'value' => $this->theme_color_secondary, 'type' => 'string']);
        Setting::updateOrCreate(['key' => 'sidebar_color'], ['group' => 'theme', 'value' => $this->sidebar_color, 'type' => 'string']);
        Setting::updateOrCreate(['key' => 'sidebar_text_color'], ['group' => 'theme', 'value' => $this->sidebar_text_color, 'type' => 'string']);
        Setting::updateOrCreate(['key' => 'welcome_message'], ['group' => 'theme', 'value' => $this->welcome_message, 'type' => 'text']);

        if ($this->app_logo) {
            $path = $this->app_logo->store('logos', 'public');
            Setting::updateOrCreate(['key' => 'app_logo'], ['group' => 'general', 'value' => $path, 'type' => 'image']);
        }

        session()->flash('success', 'Settings updated successfully.');
        
        $this->redirectRoute('admin.settings.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.settings.index')->layout('layouts.app');
    }
}
