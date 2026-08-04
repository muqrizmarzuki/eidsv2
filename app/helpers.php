<?php

if (!function_exists('setting')) {
    /**
     * Read a value from the settings table, falling back to the matching config key.
     *
     * Usage:  setting('rating_baik', 85)
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return \App\Models\Setting::get($key, $default);
    }
}
