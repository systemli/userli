<?php

namespace App\Message;

final readonly class SendWebhook
{
    public function __construct(public string $deliveryId)
    {
    }
}
