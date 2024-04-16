<?php

namespace Uspdev\Assinatura;

use Illuminate\Support\ServiceProvider;

use Uspdev\Assinatura\Models\Assinatura;
use Uspdev\Assinatura\Observers\AssinaturaObserver;

class AssinaturaServiceProvider extends ServiceProvider {
    public function boot()
    {
        
        $this->loadViewsFrom(__DIR__.'/resources/views','assinatura');
        
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        //$this->loadRoutesFrom(__DIR__.'/routes/web.php');

        Assinatura::observe(AssinaturaObserver::class);

    }
    
    public function register()
    {

        $this->mergeConfigFrom(
            __DIR__.'/config/assinatura.php', 'assinatura'
        );
        $this->publishes([
            __DIR__.'/config/assinatura.php' => config_path('assinatura.php')
        ], 'assinatura-config');
    }
}