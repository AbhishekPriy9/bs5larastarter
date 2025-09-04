<?php 

namespace AbhishekPriy9\Bs5larastarter;

use Illuminate\Support\ServiceProvider;
use AbhishekPriy9\Bs5larastarter\Commands\InstallCommand;

class Bs5larastarterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // Register the installation command
            $this->commands([
                InstallCommand::class,
            ]);

            // Make the configuration file publishable
            $this->publishes([
                __DIR__.'/../stubs/config/settings.php.stub' => config_path('settings.php'),
            ], 'bs5-config');
        }
    }
}