<?php

namespace App\Providers;

use App\Contracts\HtmlFetcher;
use App\Services\Crawler\ScrapingBeeClient;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HtmlFetcher::class, function () {
            $apiKey = (string) config('services.scrapingbee.api_key');

            if (trim($apiKey) === '') {
                throw new RuntimeException('SCRAPINGBEE_API_KEY is not configured.');
            }

            return new ScrapingBeeClient($apiKey);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
