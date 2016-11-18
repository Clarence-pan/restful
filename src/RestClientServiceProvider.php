<?php

namespace Clarence\Restful;

use Clarence\Restful\Curl\CurlRestClient;
use Illuminate\Support\ServiceProvider;

class RestClientServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('rest.client', function ($app) {
            return new CurlRestClient($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [ 'rest.client' ];
    }
}
