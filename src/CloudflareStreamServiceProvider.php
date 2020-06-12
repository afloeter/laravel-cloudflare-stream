<?php

namespace AFloeter\CloudflareStream;

use Illuminate\Support\ServiceProvider;

class CloudflareStreamServiceProvider extends ServiceProvider
{
    /**
     * Boot.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/cloudflare-stream.php' => config_path('cloudflare-stream.php'),
        ]);
    }

    /**
     * Register.
     */
    public function register()
    {
        //
    }
}
