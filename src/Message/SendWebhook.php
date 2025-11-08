<?php

declare(strict_types=1);

namespace App\Message;

final readonly class SendWebhook
{
    public function __construct(public string $deliveryId)
    {
    }
}
