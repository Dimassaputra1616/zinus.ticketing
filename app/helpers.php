<?php

if (! function_exists('setting')) {
    /**
     * Get a setting from the database, cached
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting($key, $default = null)
    {
        return \App\Models\Setting::get($key, $default);
    }
}
