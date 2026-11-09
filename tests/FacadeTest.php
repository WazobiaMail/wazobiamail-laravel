<?php

declare(strict_types=1);

namespace WazobiaMail\Laravel\Tests;

use WazobiaMail\Laravel\Facades\WazobiaMail;
use WazobiaMail\Resources\Domains;
use WazobiaMail\Resources\Mailboxes;
use WazobiaMail\Resources\MailboxMessages;
use WazobiaMail\Resources\Messages;
use WazobiaMail\Resources\SmtpRelays;
use WazobiaMail\Resources\Templates;
use WazobiaMail\Resources\Tracking;
use WazobiaMail\Resources\Webhooks;

class FacadeTest extends TestCase
{
    public function test_facade_resolves_all_resources(): void
    {
        $this->assertInstanceOf(Messages::class, WazobiaMail::messages());
        $this->assertInstanceOf(Domains::class, WazobiaMail::domains());
        $this->assertInstanceOf(Mailboxes::class, WazobiaMail::mailboxes());
        $this->assertInstanceOf(MailboxMessages::class, WazobiaMail::mailboxMessages());
        $this->assertInstanceOf(Tracking::class, WazobiaMail::tracking());
        $this->assertInstanceOf(Templates::class, WazobiaMail::templates());
        $this->assertInstanceOf(SmtpRelays::class, WazobiaMail::smtpRelays());
        $this->assertInstanceOf(Webhooks::class, WazobiaMail::webhooks());
    }
}
