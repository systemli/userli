<?php

namespace App\MessageHandler;

use App\Entity\WebhookDelivery;
use App\Message\SendWebhook;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

#[AsMessageHandler]
final readonly class SendWebhookHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface    $httpClient,
    )
    {
    }

    public function __invoke(SendWebhook $message): void
    {
        $delivery = $this->em->getRepository(WebhookDelivery::class)->find($message->deliveryId);
        if (!$delivery) {
            return;
        }

        $endpoint = $delivery->getEndpoint();
        $delivery->setAttempts($delivery->getAttempts() + 1);
        $headers = $delivery->getRequestHeaders();
        $headers['X-Webhook-Attempt'] = (string)$delivery->getAttempts();
        $delivery->setRequestHeaders($headers);

        try {
            $response = $this->httpClient->request('POST', $endpoint->getUrl(), [
                'headers' => $delivery->getRequestHeaders(),
                'json' => $delivery->getRequestBody(),
                'timeout' => 10.0,
            ]);

            $status = $response->getStatusCode();
            $body = null;
            if ($status < 200 || $status >= 300) {
                $body = substr($response->getContent(false), 0, 4096);
            }

            $delivery->setResponseCode($status);
            $delivery->setResponseBody($body);
            $delivery->setSuccess($status >= 200 && $status < 300);
            $delivery->setError(null);
        } catch (TransportExceptionInterface|Throwable $e) {
            $delivery->setSuccess(false);
            $delivery->setError(substr($e->getMessage(), 0, 4096));

            throw $e;
        } finally {
            $delivery->setDeliveredTime(new DateTimeImmutable());
            $this->em->flush();
        }
    }
}
