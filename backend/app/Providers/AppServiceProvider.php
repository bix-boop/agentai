<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register OpenAI Service
        $this->app->singleton(\App\Services\OpenAIService::class, function ($app) {
            return new \App\Services\OpenAIService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for MySQL
        \Illuminate\Database\Schema\Builder::defaultStringLength(191);
        
        // Add custom validation rules
        \Illuminate\Support\Facades\Validator::extend('openai_key', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^sk-(proj-)?[a-zA-Z0-9]{20,}$/', $value);
        });
        
        // Configure URL generation for production
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
