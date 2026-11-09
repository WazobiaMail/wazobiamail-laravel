<?php

declare(strict_types=1);

namespace WazobiaMail\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use WazobiaMail\WazobiaMailClient;

/**
 * @method static \WazobiaMail\Resources\Messages messages()
 * @method static \WazobiaMail\Resources\Domains domains()
 * @method static \WazobiaMail\Resources\Mailboxes mailboxes()
 * @method static \WazobiaMail\Resources\MailboxMessages mailboxMessages()
 * @method static \WazobiaMail\Resources\Tracking tracking()
 * @method static \WazobiaMail\Resources\Templates templates()
 * @method static \WazobiaMail\Resources\SmtpRelays smtpRelays()
 * @method static \WazobiaMail\Resources\Webhooks webhooks()
 * @method static \WazobiaMail\Configuration getConfiguration()
 * @method static \WazobiaMail\Http\HttpClient getHttpClient()
 *
 * @see \WazobiaMail\WazobiaMailClient
 */
class WazobiaMail extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'wazobiamail';
    }
}
