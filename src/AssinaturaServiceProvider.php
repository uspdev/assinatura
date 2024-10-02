<?php

namespace Uspdev\Assinatura;

use Illuminate\Support\ServiceProvider;

use Uspdev\Assinatura\Models\Assinatura;
use Uspdev\Assinatura\Models\Arquivo;
use Uspdev\Assinatura\Observers\AssinaturaObserver;
use Uspdev\Assinatura\Observers\ArquivoObserver;

class AssinaturaServiceProvider extends ServiceProvider {
    public function boot()
    {
        
        $this->loadViewsFrom(__DIR__.'/resources/views','assinatura');
        
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // investigar
        $this->loadRoutesFrom(__DIR__.'/routes/assinatura/web.php');
        //$this->loadRoutesFrom(__DIR__ . '/../routes/api.php')->prefix('api');

        Assinatura::observe(AssinaturaObserver::class);
        Arquivo::observe(ArquivoObserver::class);

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