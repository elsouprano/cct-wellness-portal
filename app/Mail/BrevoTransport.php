<?php

namespace App\Mail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            $data = $response->json();
            $code = is_array($data) ? $this->sanitizeErrorField($data['code'] ?? null, 64) : null;
            $detail = is_array($data) ? $this->sanitizeErrorField($data['message'] ?? null, 200) : null;

            Log::warning('Brevo API rejected email request.', [
                'status' => $response->status(),
                'code' => $code,
                'message' => $detail,
            ]);

            $suffix = $code ? ", {$code}" : '';
            $suffix .= $detail ? ": {$detail}" : '';

            throw new \RuntimeException('Brevo API rejected email request (HTTP ' . $response->status() . $suffix . ').');
        }
    }

    private function sanitizeErrorField(mixed $value, int $maxLength): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $text = (string) $value;
        if ($this->apiKey !== '') {
            $text = str_replace($this->apiKey, '[REDACTED]', $text);
        }

        $text = trim((string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', $text));
        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, $maxLength);
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
