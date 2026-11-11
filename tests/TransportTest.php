<?php

declare(strict_types=1);

namespace WazobiaMail\Laravel\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use WazobiaMail\Exceptions\ForbiddenException;
use WazobiaMail\Laravel\Transport\WazobiaMailTransport;
use WazobiaMail\Resources\Messages;
use WazobiaMail\WazobiaMailClient;

class TransportTest extends PHPUnitTestCase
{
    public function test_transport_sends_email_with_full_payload(): void
    {
        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $payload) {
                return $payload['from'] === 'Sender <sender@example.com>'
                    && in_array('Recipient <recipient@example.com>', $payload['to'], true)
                    && in_array('cc@example.com', $payload['cc'], true)
                    && in_array('bcc@example.com', $payload['bcc'], true)
                    && $payload['replyTo'] === 'Reply <reply@example.com>'
                    && $payload['subject'] === 'Test Subject'
                    && $payload['text'] === 'Plain text content'
                    && $payload['html'] === '<h1>HTML content</h1>'
                    && count($payload['attachments']) === 1
                    && $payload['attachments'][0]['filename'] === 'test.txt'
                    && $payload['attachments'][0]['content'] === base64_encode('hello world');
            }))
            ->willReturn([
                'result' => [
                    'trackingId' => 'msg_xyz_123',
                    'status' => 'queued',
                ],
            ]);

        $clientMock = $this->createMock(WazobiaMailClient::class);
        $clientMock->method('messages')->willReturn($messagesMock);

        $transport = new WazobiaMailTransport($clientMock);

        $email = (new Email())
            ->from(new Address('sender@example.com', 'Sender'))
            ->to(new Address('recipient@example.com', 'Recipient'))
            ->cc(new Address('cc@example.com'))
            ->bcc(new Address('bcc@example.com'))
            ->replyTo(new Address('reply@example.com', 'Reply'))
            ->subject('Test Subject')
            ->text('Plain text content')
            ->html('<h1>HTML content</h1>')
            ->attach('hello world', 'test.txt', 'text/plain');

        $sentMessage = $transport->send($email);

        $this->assertNotNull($sentMessage);
        $original = $sentMessage->getOriginalMessage();
        $this->assertInstanceOf(Message::class, $original);
        $this->assertSame('msg_xyz_123', $original->getHeaders()->get('X-Wazobia-Message-Id')?->getBodyAsString());
        $this->assertSame('wazobiamail', (string) $transport);
    }

    public function test_transport_sends_minimal_email(): void
    {
        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $payload) {
                return $payload['from'] === 'sender@example.com'
                    && in_array('user@example.com', $payload['to'], true)
                    && $payload['subject'] === 'Minimal Subject'
                    && $payload['html'] === '<p>Hello</p>'
                    && !isset($payload['cc'])
                    && !isset($payload['bcc'])
                    && !isset($payload['attachments']);
            }))
            ->willReturn([
                'id' => 'msg_minimal_456',
            ]);

        $clientMock = $this->createMock(WazobiaMailClient::class);
        $clientMock->method('messages')->willReturn($messagesMock);

        $transport = new WazobiaMailTransport($clientMock);

        $email = (new Email())
            ->from('sender@example.com')
            ->to('user@example.com')
            ->subject('Minimal Subject')
            ->html('<p>Hello</p>');

        $sentMessage = $transport->send($email);

        $this->assertNotNull($sentMessage);
        $original = $sentMessage->getOriginalMessage();
        $this->assertInstanceOf(Message::class, $original);
        $this->assertSame('msg_minimal_456', $original->getHeaders()->get('X-Wazobia-Message-Id')?->getBodyAsString());
    }

    public function test_transport_rethrows_wazobiamail_exceptions(): void
    {
        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('send')
            ->willThrowException(new ForbiddenException('Enterprise plan required to send via custom domain', 403));

        $clientMock = $this->createMock(WazobiaMailClient::class);
        $clientMock->method('messages')->willReturn($messagesMock);

        $transport = new WazobiaMailTransport($clientMock);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Enterprise plan required to send via custom domain');

        $email = (new Email())
            ->from('sender@example.com')
            ->to('user@example.com')
            ->subject('Subject')
            ->text('Text');

        $transport->send($email);
    }
}
