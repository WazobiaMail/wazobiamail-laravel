<?php

declare(strict_types=1);

namespace WazobiaMail\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use WazobiaMail\Laravel\Facades\WazobiaMail;
use WazobiaMail\Laravel\WazobiaMailServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            WazobiaMailServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'WazobiaMail' => WazobiaMail::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('wazobiamail.api_key', 'wzbm_test_key_12345');
        $app['config']->set('wazobiamail.base_url', 'https://app.wazobiamail.com/api/v1');
        $app['config']->set('wazobiamail.timeout', 15.0);

        $app['config']->set('mail.mailers.wazobiamail', [
            'transport' => 'wazobiamail',
        ]);
        $app['config']->set('mail.default', 'wazobiamail');
    }
}
