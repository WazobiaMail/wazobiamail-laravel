<?php

declare(strict_types=1);

namespace WazobiaMail\Laravel\Tests;

use WazobiaMail\Laravel\Transport\WazobiaMailTransport;
use WazobiaMail\WazobiaMailClient;

class ServiceProviderTest extends TestCase
{
    public function test_it_registers_wazobiamail_singleton(): void
    {
        $client1 = $this->app->make(WazobiaMailClient::class);
        $client2 = $this->app->make('wazobiamail');

        $this->assertInstanceOf(WazobiaMailClient::class, $client1);
        $this->assertSame($client1, $client2);
        $this->assertSame('wzbm_test_key_12345', $client1->getConfiguration()->getApiKey());
        $this->assertSame('https://app.wazobiamail.com/api/v1/', $client1->getConfiguration()->getBaseUrl());
        $this->assertSame(15.0, $client1->getConfiguration()->getTimeout());
    }

    public function test_it_registers_custom_mail_transport(): void
    {
        $mailManager = $this->app->make('mail.manager');
        $mailer = $mailManager->mailer('wazobiamail');

        $transport = $mailer->getSymfonyTransport();

        $this->assertInstanceOf(WazobiaMailTransport::class, $transport);
    }

    public function test_it_supports_custom_credentials_in_mail_config(): void
    {
        $this->app['config']->set('mail.mailers.custom_wazobia', [
            'transport' => 'wazobiamail',
            'api_key' => 'wzbm_custom_key_99999',
            'base_url' => 'https://custom.wazobiamail.com/api/v1',
            'timeout' => 45.0,
        ]);

        $mailManager = $this->app->make('mail.manager');
        $transport = $mailManager->mailer('custom_wazobia')->getSymfonyTransport();

        $this->assertInstanceOf(WazobiaMailTransport::class, $transport);
    }
}
