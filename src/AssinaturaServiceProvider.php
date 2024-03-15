<?php

namespace Uspdev\Assinatura;

use Illuminate\Support\ServiceProvider;

class AssinaturaServiceProvider extends ServiceProvider {
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/resources/views','assinatura');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        /*
        $this->mergeConfigFrom(
            __DIR__.'/config/assinatura.php', 'assinatura'
        );
        $this->publishes([
            __DIR__.'/config/assinatura.php' => config_path('assinatura.php')
        ], 'assinatura-config');*/
        
    }
    
    public function register()
    {
        //
    }
}