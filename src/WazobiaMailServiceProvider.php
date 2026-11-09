<?php

declare(strict_types=1);

namespace WazobiaMail\Laravel;

use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider;
use WazobiaMail\Laravel\Transport\WazobiaMailTransport;
use WazobiaMail\WazobiaMailClient;

class WazobiaMailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/wazobiamail.php',
            'wazobiamail'
        );

        $this->app->singleton(WazobiaMailClient::class, function ($app) {
            $config = $app['config']->get('wazobiamail', []);

            return new WazobiaMailClient([
                'api_key' => $config['api_key'] ?? null,
                'base_url' => $config['base_url'] ?? 'https://app.wazobiamail.com/api/v1',
                'timeout' => $config['timeout'] ?? 30.0,
            ]);
        });

        $this->app->alias(WazobiaMailClient::class, 'wazobiamail');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/wazobiamail.php' => $this->app->configPath('wazobiamail.php'),
            ], 'wazobiamail-config');
        }

        $this->registerTransport();
    }

    /**
     * Register the WazobiaMail transport with Laravel's MailManager.
     */
    protected function registerTransport(): void
    {
        $this->callAfterResolving('mail.manager', function (MailManager $manager) {
            $manager->extend('wazobiamail', function (array $config = []) {
                $apiKey = $config['api_key'] ?? $this->app['config']->get('wazobiamail.api_key');
                $baseUrl = $config['base_url'] ?? $this->app['config']->get('wazobiamail.base_url', 'https://app.wazobiamail.com/api/v1');
                $timeout = $config['timeout'] ?? $this->app['config']->get('wazobiamail.timeout', 30.0);

                $client = new WazobiaMailClient([
                    'api_key' => $apiKey,
                    'base_url' => $baseUrl,
                    'timeout' => $timeout,
                ]);

                return new WazobiaMailTransport($client);
            });
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            WazobiaMailClient::class,
            'wazobiamail',
        ];
    }
}
