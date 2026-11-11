# WazobiaMail Laravel SDK

**Start free with 3,000 emails/month.** WazobiaMail is a Resend alternative for transactional email through an API or SMTP, with sending domains, hosted mailboxes and deliverability tools.

[![Latest Version](https://img.shields.io/packagist/v/wazobiamail/wazobiamail-laravel.svg?style=flat-square)](https://packagist.org/packages/wazobiamail/wazobiamail-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/wazobiamail/wazobiamail-laravel.svg?style=flat-square)](https://packagist.org/packages/wazobiamail/wazobiamail-laravel)
[![License](https://img.shields.io/packagist/l/wazobiamail/wazobiamail-laravel.svg?style=flat-square)](LICENSE)
[![Docs](https://img.shields.io/badge/Docs-docs.wazobiamail.com-blueviolet.svg?style=flat-square)](https://docs.wazobiamail.com)

Official Laravel integration package for [WazobiaMail](https://wazobiamail.com). Provides seamless integration with Laravel's native `Mail` manager, custom `wazobiamail` mail driver, an expressive `WazobiaMail` Facade, and full support for Enterprise features (Mailbox Inboxes, Domain DNS auto-sync, Webhooks).

> 📚 **Official Platform Documentation**: Visit [docs.wazobiamail.com](https://docs.wazobiamail.com) for exhaustive API references, architecture guides, DNS setup walkthroughs, and webhook configurations.

---

## Requirements

- **PHP**: `^8.1 || ^8.2 || ^8.3 || ^8.4 || ^8.5`
- **Laravel Framework**: `^10.0 || ^11.0 || ^12.0 || ^13.0`

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

##### 🌐 Subdomain vs. Apex / Root Domain Architecture & Edge Cases

When configuring custom domains for transactional sending, choosing between a **subdomain** (e.g. `mail.example.com` or `notifications.example.com`) and an **apex/root domain** (`example.com`) is a critical architectural decision.

- **Preserve Existing Business Email (Google Workspace / Microsoft 365):**
  If your root domain (`example.com`) already receives corporate email via Google Workspace or Microsoft 365, adding MX records pointing to WazobiaMail at the root apex `@` will **break your existing company email inbox routing**. Using `mail.example.com` isolates transactional email routing completely.
- **Isolate Sender Reputation:**
  Transactional emails (password resets, invoices, OTP codes) have high engagement and deliverability requirements. Sending from a subdomain (`mail.example.com`) ensures that any accidental marketing spam complaints won't degrade your corporate employee email deliverability.
- **Avoid SPF 10-Lookup Limit Exhaustion (RFC 7208):**
  SPF records have a strict limit of 10 DNS lookups. Root domains often accumulate multiple SaaS providers (Google, Zendesk, Salesforce, HubSpot). A dedicated subdomain gives WazobiaMail its own clean, independent SPF record without lookup count contention.

###### DNS Hostnames When Root Domain is NOT Used

When verifying a subdomain like `notifications.example.com`, ensure you configure your DNS provider (Cloudflare, Route53, Namecheap, GoDaddy) with the correct hostnames:

| Record Type | Host / Name (if domain is `notifications.example.com`) | Target / Value | Notes |
| :--- | :--- | :--- | :--- |
| **TXT (SPF)** | `notifications` | `v=spf1 include:_spf.wazobiamail.com ~all` | **Do NOT** place on `@` root |
| **TXT (DKIM)** | `wzbm._domainkey.notifications` | `k=rsa; p=MIGfMA0GCSqGSIb3...` | Selector is scoped to the subdomain |
| **MX (Hosted)**| `notifications` (priority 10 / 20) | `mx1.wazobiamail.com` / `mx2.wazobiamail.com` | Only required if receiving mail or hosted inboxes |
| **CNAME (Tracking)** | `email.notifications` | `trk.wazobiamail.com` | Used for custom branded click/open tracking |
| **TXT (DMARC)** | `_dmarc.notifications` (or fallback to `_dmarc`) | `v=DMARC1; p=reject; rua=mailto:dmarc@...` | Resolvers fall back to apex `_dmarc` if omitted |

###### Critical Edge Cases & Troubleshooting

- **⚠️ UnverifiedSenderDomain (HTTP 422 / 403)**: If you verified `notifications.example.com` in WazobiaMail, your `from` address in your Mailable **must** match that subdomain (e.g. `notifications@notifications.example.com`). Attempting to send from an unverified root domain will throw a `ValidationException`.
- **💡 Reply-To Decoupling (Best Practice)**: You can send from the verified subdomain while routing customer replies to your corporate root domain mailbox:
  ```php
  Mail::to($user->email)->send(
      (new OrderShipped($order))
          ->from('orders@notifications.example.com', 'Store Orders')
          ->replyTo('support@example.com', 'Customer Support')
  );
  ```
- **☁️ Cloudflare Proxy Status (Grey Cloud vs. Orange Cloud)**: In Cloudflare DNS, leaving the proxy enabled (**Orange Cloud**) for mail or tracking records breaks SMTP traffic and DNS verification. Always toggle Cloudflare records to **"DNS only" (Grey Cloud / Proxied: Off)**.
- **⏳ Negative DNS Caching (SOA Minimum TTL Delay)**: If you trigger domain verification before DNS propagates globally, recursive resolvers cache negative lookups (`NXDOMAIN`). Wait 5 to 60 minutes or check `dig TXT notifications.example.com` before triggering verification.
- **🛡️ DMARC Subdomain Policy (`sp=`) Inheritance**: If your apex domain (`example.com`) has a DMARC record with `sp=reject`, subdomains automatically inherit that rejection policy. Ensure both SPF and DKIM pass on the subdomain before sending production traffic.
- **📬 Hosted Mailboxes on Subdomains**: When provisioning hosted mailboxes, mailboxes are created under the specific verified domain (e.g. `support@notifications.example.com`). Inbound receiving requires the MX records for that exact subdomain to point to WazobiaMail.
- **🔁 Network Timeouts & Idempotency**: Provide an `idempotencyKey` when dispatching critical transactional messages to prevent duplicate delivery on retry.

#### Delivery Tracking, Stats, Read Receipts & Click Receipts

WazobiaMail provides built-in, granular observability for every transactional email dispatched.

- **Read Receipts (Open Tracking)**: An invisible 1x1 tracking pixel records when recipients open your email, capturing timestamp, IP geolocation, user-agent, and open counts.
- **Click Receipts (Link Tracking)**: Links in HTML content are automatically rewritten to your tracking domain, logging each click event, recipient, destination URL, and click counts.
- **Delivery Lifecycle**: Real-time status progression from `queued` ➔ `sent` ➔ `delivered` ➔ `opened` ➔ `clicked` ➔ `bounced`.

```php
// Inspect single message timeline and receipt counters
$timeline = WazobiaMail::tracking()->timeline('msg_abc123');

echo "Status: " . $timeline['data']['status'] . "\n";
echo "Read Receipts (Opens): " . $timeline['data']['openCount'] . "\n";
echo "Click Receipts (Clicks): " . $timeline['data']['clickCount'] . "\n";

// Query delivery events
$events = WazobiaMail::tracking()->events([
    'event_type' => 'delivered',
    'recipient' => 'client@example.com',
]);
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

---

## Reading spam-check scores

The spam check returns a penalty `score`, a `threshold`, an `isSpam` boolean and an inverse `deliverabilityScore` (0–100, higher is better).

| `score` | `rating` | `isSpam` | `deliverabilityScore` | Decision |
| --- | --- | --- | --- | --- |
| `0.0 – 1.9` | `low_risk` | `false` | `81 – 100` | **Okay to send.** Clean or only minor findings. |
| `2.0 – 4.9` | `moderate_risk` | `false` | `51 – 80` | **Review first.** Not blocked, but fix the highest-point rules before sending. |
| `5.0 +` | `high_risk` | `true` | `0 – 50` | **Do not send.** Fix the findings and re-run the check. |

`isSpam` is the authoritative decision: it is true when `score >= threshold`. Read `threshold` from the response instead of hard-coding `5.0`; the server can change it. A green score is not a delivery guarantee — domain authentication, sender reputation and recipient engagement still matter.

## Official WazobiaMail SDKs

WazobiaMail is a **Resend alternative** for transactional email through an **API or SMTP**, with sending domains, hosted mailboxes and deliverability tools. Start free with **3,000 emails/month**.

The SDKs below all use the WazobiaMail API; SMTP/legacy clients that need SMTP submission can use WazobiaMail's SMTP access from the dashboard. Choose the package that matches your stack:

| Platform | Install | Registry | Source |
| --- | --- | --- | --- |
| TypeScript / Node.js | `npm install wazobiamail` | [npm](https://www.npmjs.com/package/wazobiamail) | [GitHub](https://github.com/WazobiaMail/wazobiamail-node) |
| Vue 3 | `npm install wazobiamail-vue wazobiamail` | [npm](https://www.npmjs.com/package/wazobiamail-vue) | [GitHub](https://github.com/WazobiaMail/wazobiamail-vue) |
| Nuxt 3 & 4 | `npm install wazobiamail-nuxt` | [npm](https://www.npmjs.com/package/wazobiamail-nuxt) | [GitHub](https://github.com/WazobiaMail/wazobiamail-nuxt) |
| Python | `pip install wazobiamail` | [PyPI](https://pypi.org/project/wazobiamail/) | [GitHub](https://github.com/WazobiaMail/wazobiamail-python) |
| PHP | `composer require wazobiamail/wazobiamail-php` | [Packagist](https://packagist.org/packages/wazobiamail/wazobiamail-php) | [GitHub](https://github.com/WazobiaMail/wazobiamail-php) |
| Laravel | `composer require wazobiamail/wazobiamail-laravel` | [Packagist](https://packagist.org/packages/wazobiamail/wazobiamail-laravel) | [GitHub](https://github.com/WazobiaMail/wazobiamail-laravel) |
| Rust | `cargo add wazobiamail` | [crates.io](https://crates.io/crates/wazobiamail) | [GitHub](https://github.com/WazobiaMail/wazobiamail-rust) |
| WordPress | Upload the plugin ZIP | [Packagist](https://packagist.org/packages/wazobiamail/wazobiamail-wordpress) · [GitHub releases](https://github.com/WazobiaMail/wazobiamail-wordpress/releases) | [GitHub](https://github.com/WazobiaMail/wazobiamail-wordpress) |

### What each package gives you

- **TypeScript / Node.js (`wazobiamail`)** — A zero-production-dependency typed client for Node.js 18+, Bun, Deno and edge runtimes. It ships ESM and CommonJS builds, typed errors, request builders, URL/multipart attachments, and resources for messages, domains, mailboxes, API keys, billing, units, plans, tracking, spam checks and support.
- **Vue 3 (`wazobiamail-vue`)** — A Vue plugin plus reactive composables: `useSendMail` for loading/success/error state, `useMailbox` for paginated headless inboxes with polling and optimistic delete, `useDomains` for domain/DNS workflows, and `useSpamCheck` for pre-flight deliverability checks.
- **Nuxt 3 & 4 (`wazobiamail-nuxt`)** — Keeps `runtimeConfig.wazobiamail.apiKey` on the Nitro server, adds the auto-imported `serverWazobiaClient(event)` helper, and auto-imports the Vue composables. Ideal for Nuxt apps that must not expose an API key to the browser bundle.
- **Python (`wazobiamail`)** — A synchronous, PEP 561-compliant client with **zero runtime dependencies** (standard library only), dataclass models and typed responses. Works in Django (sync views or `sync_to_async`), FastAPI (`def` endpoints or `run_in_threadpool`), Flask, scripts and workers. Includes webhook HMAC verification.
- **PHP (`wazobiamail/wazobiamail-php`)** — A Composer/Guzzle 7 client for PHP 8.1+. It accepts ergonomic flat send payloads or the canonical nested `content` shape, supports URL attachments and automatic multipart upload for inline files, and maps API failures to typed exceptions.
- **Laravel (`wazobiamail/wazobiamail-laravel`)** — Registers a native `wazobiamail` mail driver, service provider and `WazobiaMail` facade for Laravel 10–13. Existing `Mail`, Mailables and notifications keep working; the package also exposes domains, mailboxes and webhook verification.
- **Rust (`wazobiamail`)** — Async, strongly typed and `#![forbid(unsafe_code)]`, built on reqwest/tokio. Includes fluent request builders, typed response models, typed errors, secret masking in `Debug`, and resources for sending, domains, Cloudflare DNS, mailboxes, templates, tracking and billing.
- **WordPress (`wazobiamail-wordpress`)** — Routes every `wp_mail()` call through the WazobiaMail API without changing forms or plugins. It registers the site domain, displays SPF/DKIM/DMARC/MX records, verifies DNS, uploads attachments, and keeps recent delivery receipts in the admin screen.

### Documentation links

- [Official SDK guide and examples](https://docs.wazobiamail.com/docs/guides/sdks)
- [API reference](https://docs.wazobiamail.com/docs/api-reference)
- [Authentication and API keys](https://docs.wazobiamail.com/docs/guides/authentication)
- [Sending domains and DNS](https://docs.wazobiamail.com/docs/guides/domains)
- [Webhooks](https://docs.wazobiamail.com/docs/guides/webhooks)
