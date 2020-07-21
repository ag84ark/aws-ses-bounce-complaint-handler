<?php

namespace ag84ark\AwsSesBounceComplaintHandler;

use Illuminate\Support\ServiceProvider;

class AwsSesBounceComplaintHandlerServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'ag84ark');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'ag84ark');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/aws-ses-bounce-complaint-handler.php', 'aws-ses-bounce-complaint-handler');

        // Register the service the package provides.
        $this->app->singleton('aws-ses-bounce-complaint-handler', function ($app) {
            return new AwsSesBounceComplaintHandler;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['aws-ses-bounce-complaint-handler'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/aws-ses-bounce-complaint-handler.php' => config_path('aws-ses-bounce-complaint-handler.php'),
        ], 'aws-ses-bounce-complaint-handler.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/ag84ark'),
        ], 'aws-ses-bounce-complaint-handler.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/ag84ark'),
        ], 'aws-ses-bounce-complaint-handler.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/ag84ark'),
        ], 'aws-ses-bounce-complaint-handler.views');*/

        // Register)ing package commands.
        //        // $this->commands([];
    }
}
