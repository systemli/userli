<?php

namespace App\Handler;

use App\Entity\User;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class WebhookHandler
{
    public function __construct(
        private HttpClientInterface $client,
        private string              $webhookUrl,
    ) {
    }

    public function send(User $user, string $type): void {
        if (!$this->webhookUrl) {
            return;
        }

        try {
            $this->client->request('POST', $this->webhookUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'type' => $type,
                    'timestamp' => date(DATE_ATOM),
                    'data' => [
                        'email' => $user->getEmail(),
                    ],
                ],
            ]);
        } catch (\RuntimeException | TransportExceptionInterface) {
            // Ignore failed requests
        }
    }
}
