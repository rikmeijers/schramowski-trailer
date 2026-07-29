<?php

if (! function_exists('versioned_asset')) {
    /**
     * Build a public asset URL with a cache-busting query string.
     * Critical for iOS Safari / installed PWAs that cannot hard-refresh.
     */
    function versioned_asset(string $path): string
    {
        $relative = ltrim($path, '/');
        $fullPath = dirname(base_path()).'/public/'.$relative;
        $mtime = is_file($fullPath) ? filemtime($fullPath) : 0;
        $version = config('app.version', '1').'-'.$mtime;

        return url('/'.$relative).'?v='.rawurlencode($version);
    }
}
