<?php

declare(strict_types=1);

namespace WazobiaMail\Laravel\Transport;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;
use WazobiaMail\WazobiaMailClient;

class WazobiaMailTransport extends AbstractTransport
{
    public function __construct(
        protected WazobiaMailClient $client
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $payload = $this->formatPayload($email);

        $result = $this->client->messages()->send($payload);

        if (!empty($result['id'])) {
            $original = $message->getOriginalMessage();
            if ($original instanceof \Symfony\Component\Mime\Message) {
                $original->getHeaders()->addTextHeader('X-Wazobia-Message-Id', (string) $result['id']);
            }
        }
    }

    /**
     * Convert Symfony Email into the WazobiaMail API payload format.
     *
     * @param Email $email
     * @return array<string, mixed>
     */
    protected function formatPayload(Email $email): array
    {
        $payload = [
            'subject' => $email->getSubject() ?? '',
            'to' => $this->formatAddresses($email->getTo()),
        ];

        $from = $this->formatAddresses($email->getFrom());
        if (!empty($from)) {
            $payload['from'] = $from[0];
        }

        $cc = $this->formatAddresses($email->getCc());
        if (!empty($cc)) {
            $payload['cc'] = $cc;
        }

        $bcc = $this->formatAddresses($email->getBcc());
        if (!empty($bcc)) {
            $payload['bcc'] = $bcc;
        }

        $replyTo = $this->formatAddresses($email->getReplyTo());
        if (!empty($replyTo)) {
            $payload['replyTo'] = $replyTo[0];
        }

        $html = $email->getHtmlBody();
        if ($html !== null) {
            $payload['html'] = (string) $html;
        }

        $text = $email->getTextBody();
        if ($text !== null) {
            $payload['text'] = (string) $text;
        }

        $attachments = $this->formatAttachments($email->getAttachments());
        if (!empty($attachments)) {
            $payload['attachments'] = $attachments;
        }

        return $payload;
    }

    /**
     * @param array<Address> $addresses
     * @return array<string>
     */
    protected function formatAddresses(array $addresses): array
    {
        $formatted = [];
        foreach ($addresses as $address) {
            $formatted[] = $address->getName() !== ''
                ? sprintf('%s <%s>', $address->getName(), $address->getAddress())
                : $address->getAddress();
        }

        return $formatted;
    }

    /**
     * @param array<DataPart> $attachments
     * @return array<int, array{filename: string, content: string, contentType: string}>
     */
    protected function formatAttachments(array $attachments): array
    {
        $formatted = [];
        foreach ($attachments as $attachment) {
            $formatted[] = [
                'filename' => $attachment->getFilename() ?? 'attachment',
                'content' => base64_encode($attachment->getBody()),
                'contentType' => $attachment->getContentType(),
            ];
        }

        return $formatted;
    }

    public function __toString(): string
    {
        return 'wazobiamail';
    }
}
