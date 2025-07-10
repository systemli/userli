<?php

namespace App\Handler;

use RuntimeException;
use App\Entity\User;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class WebhookHandler
{
    public function __construct(
        private HttpClientInterface $client,
        private string              $webhookUrl,
        private string              $webhookSecret,
    ) {
    }

    public function send(User $user, string $type): void {
        if (!$this->webhookUrl || !$this->webhookSecret) {
            return;
        }

        $payload = [
            'type' => $type,
            'timestamp' => date(DATE_ATOM),
            'data' => [
                'email' => $user->getEmail(),
            ],
        ];
        $signature = hash_hmac('sha256', json_encode($payload), $this->webhookSecret);

        try {
            $this->client->request('POST', $this->webhookUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Signature' => $signature,
                ],
                'json' => $payload,
            ]);
        } catch (RuntimeException | TransportExceptionInterface) {
            // Ignore failed requests
        }
    }
}
