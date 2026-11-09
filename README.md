# WazobiaMail Laravel SDK

[![Latest Version](https://img.shields.io/packagist/v/wazobiamail/wazobiamail-laravel.svg?style=flat-square)](https://packagist.org/packages/wazobiamail/wazobiamail-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/wazobiamail/wazobiamail-laravel.svg?style=flat-square)](https://packagist.org/packages/wazobiamail/wazobiamail-laravel)
[![License](https://img.shields.io/packagist/l/wazobiamail/wazobiamail-laravel.svg?style=flat-square)](LICENSE)

Official Laravel integration package for [WazobiaMail](https://wazobiamail.com). Provides seamless integration with Laravel's native `Mail` manager, custom `wazobiamail` mail driver, an expressive `WazobiaMail` Facade, and full support for Enterprise features (Mailbox Inboxes, Domain DNS auto-sync, Webhooks).

---

## Requirements

- **PHP**: `^8.1 || ^8.2 || ^8.3 || ^8.4 || ^8.5`
- **Laravel Framework**: `^10.0 || ^11.0`

---

## Installation

Install the package via Composer:

```bash
composer require wazobiamail/wazobiamail-laravel
```

The package uses Laravel's package auto-discovery, so its `WazobiaMailServiceProvider` and `WazobiaMail` facade will register automatically.

---

## Configuration

### 1. Publish Configuration

Publish the `wazobiamail.php` config file:

```bash
php artisan vendor:publish --tag=wazobiamail-config
```

This creates `config/wazobiamail.php`:

```php
return [
    'api_key' => env('WAZOBIA_API_KEY'),
    'base_url' => env('WAZOBIA_BASE_URL', 'https://app.wazobiamail.com/api/v1'),
    'timeout' => (float) env('WAZOBIA_TIMEOUT', 30.0),
];
```

### 2. Environment Variables

Add your WazobiaMail API key to your `.env` file:

```dotenv
WAZOBIA_API_KEY=wzbm_live_your_api_key_here
MAIL_MAILER=wazobiamail
```

### 3. Mail Configuration

In `config/mail.php`, ensure the `wazobiamail` mailer is registered:

```php
'mailers' => [
    'wazobiamail' => [
        'transport' => 'wazobiamail',
    ],
    // ...
],
```

---

## Usage

### 1. Native Laravel Mail

When `MAIL_MAILER=wazobiamail` is set in your `.env`, all standard Laravel mail functions (Mails, Mailables, Notifications) are routed through WazobiaMail's high-speed API:

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

Mail::to('customer@example.com')->send(new WelcomeEmail());
```

Or using raw emails:

```php
Mail::raw('Welcome to our service!', function ($message) {
    $message->to('customer@example.com')
            ->subject('Welcome!')
            ->from('hello@yourdomain.com', 'Our Company');
});
```

---

### 2. WazobiaMail Facade

You can also use the `WazobiaMail` facade to interact directly with the WazobiaMail REST API resources:

```php
use WazobiaMail\Laravel\Facades\WazobiaMail;
```

#### Sending Messages Directly
```php
$response = WazobiaMail::messages()->send([
    'from' => 'Team <noreply@yourdomain.com>',
    'to' => ['client@example.com'],
    'subject' => 'Monthly Statement',
    'html' => '<p>Here is your statement</p>',
    'text' => 'Here is your statement',
]);

echo $response['id']; // msg_...
```

#### Enterprise Inbox & Mailbox Messages
Read and manage messages stored in user mailboxes:

```php
// List inbox messages
$inbox = WazobiaMail::mailboxMessages()->list('mb_12345', [
    'folder' => 'INBOX',
    'page' => 1,
    'per_page' => 20,
]);

// Fetch a single message with body & attachments
$email = WazobiaMail::mailboxMessages()->get('mb_12345', 'msg_98765');

// Delete a message
WazobiaMail::mailboxMessages()->delete('mb_12345', 'msg_98765');
```

#### Managing Mailboxes
```php
// Create mailbox
$mailbox = WazobiaMail::mailboxes()->create([
    'domain_id' => 'dom_12345',
    'email_prefix' => 'support',
    'password' => 'StrongPassword123!',
    'quota_mb' => 2048,
]);

// List mailboxes
$mailboxes = WazobiaMail::mailboxes()->list();

// Rotate password
WazobiaMail::mailboxes()->updatePassword('mb_12345', 'NewStrongPassword456!');
```

#### Sending Domains & DNS Verification
```php
// Register a new domain
$domain = WazobiaMail::domains()->create([
    'domain_name' => 'notifications.example.com',
]);

// Get DNS setup records (SPF, DKIM, MX, DMARC)
$dns = WazobiaMail::domains()->getDnsRecords('dom_12345');

// Trigger verification
$status = WazobiaMail::domains()->verify('dom_12345');
```

#### Delivery Tracking & Analytics
```php
// Query delivery events
$events = WazobiaMail::tracking()->events([
    'event_type' => 'delivered',
    'recipient' => 'client@example.com',
]);

// Get delivery timeline for a message
$timeline = WazobiaMail::tracking()->timeline('msg_abc123');
```

#### Webhook Handling & Verification
In your Laravel webhook controller:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use WazobiaMail\Laravel\Facades\WazobiaMail;
use WazobiaMail\Exceptions\ValidationException;

class WazobiaWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $signature = $request->header('X-Wazobia-Signature');
        $timestamp = (int) $request->header('X-Wazobia-Timestamp');
        $secret = config('services.wazobia.webhook_secret');

        try {
            $event = WazobiaMail::webhooks()->constructEvent(
                payload: $request->getContent(),
                signature: $signature,
                timestamp: $timestamp,
                secret: $secret
            );
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        match ($event['event']) {
            'delivered' => logger('Email delivered to ' . $event['recipient']),
            'bounced'   => logger('Email bounced: ' . $event['reason']),
            default     => null,
        };

        return response()->json(['received' => true]);
    }
}
```

---

## Error Handling

The package throws specific, typed exceptions that extend `WazobiaMail\Exceptions\WazobiaException`:

| Exception | HTTP Status | Description |
|:---|:---|:---|
| `AuthenticationException` | 401 | Invalid or missing API key |
| `ForbiddenException` | 403 | Plan restriction or missing permissions. Call `$e->isEnterprisePlanRequired()` to check if an Enterprise upgrade is needed. |
| `NotFoundException` | 404 | Requested resource (domain, mailbox, message) was not found |
| `ValidationException` | 422 | Payload failed schema validation. Retrieve field errors via `$e->getErrors()`. |
| `RateLimitException` | 429 | Rate limit exceeded. Call `$e->getRetryAfter()` for retry window. |
| `ServerException` | 500+ | WazobiaMail internal or upstream gateway error |

---

## Testing

Run the test suite using PHPUnit:

```bash
composer test
```

---

## Security Vulnerabilities

If you discover any security vulnerabilities, please send an e-mail to security@wazobiamail.com.

---

## License

The WazobiaMail Laravel package is open-sourced software licensed under the [MIT license](LICENSE).
