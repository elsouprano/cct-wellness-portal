<?php

namespace App\Mail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class BrevoTransport extends AbstractTransport
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    public function __construct(
        private readonly string $apiKey,
        private readonly int $timeoutSeconds = 10,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $fromAddresses = $email->getFrom();
        if (! empty($fromAddresses)) {
            $sender = $this->formatAddress($fromAddresses[0]);
        } else {
            $fallbackAddress = (string) config('mail.from.address', '');
            $fallbackName = (string) config('mail.from.name', '');
            if ($fallbackAddress === '') {
                throw new \RuntimeException('Brevo sender email address is not configured.');
            }
            $sender = ['email' => $fallbackAddress];
            if ($fallbackName !== '') {
                $sender['name'] = $fallbackName;
            }
        }

        $to = array_map($this->formatAddress(...), $email->getTo());
        if (empty($to)) {
            throw new \RuntimeException('Brevo email request requires at least one recipient.');
        }

        $payload = [
            'sender' => $sender,
            'to' => $to,
            'subject' => (string) ($email->getSubject() ?? ''),
        ];

        $cc = array_map($this->formatAddress(...), $email->getCc());
        if (! empty($cc)) {
            $payload['cc'] = $cc;
        }

        $bcc = array_map($this->formatAddress(...), $email->getBcc());
        if (! empty($bcc)) {
            $payload['bcc'] = $bcc;
        }

        $replyTo = $email->getReplyTo();
        if (! empty($replyTo)) {
            $payload['replyTo'] = $this->formatAddress($replyTo[0]);
        }

        $html = $email->getHtmlBody();
        if (is_string($html) && $html !== '') {
            $payload['htmlContent'] = $html;
        }

        $text = $email->getTextBody();
        if (is_string($text) && $text !== '') {
            $payload['textContent'] = $text;
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'accept' => 'application/json',
            ])->asJson()->timeout($this->timeoutSeconds)->post(self::ENDPOINT, $payload);
        } catch (ConnectionException $e) {
            throw new \RuntimeException('Brevo API request timed out or failed to connect.', 0, $e);
        }

        if ($response->failed()) {
            throw new \RuntimeException('Brevo API rejected email request (HTTP ' . $response->status() . ').');
        }
    }

    /**
     * Format a Symfony MIME Address into Brevo's recipient/sender shape.
     *
     * @return array{email: string, name?: string}
     */
    private function formatAddress(Address $address): array
    {
        $formatted = ['email' => $address->getAddress()];
        $name = trim($address->getName());

        if ($name !== '') {
            $formatted['name'] = $name;
        }

        return $formatted;
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
