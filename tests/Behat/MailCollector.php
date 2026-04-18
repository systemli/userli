<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

/**
 * Records every mail the Symfony mailer dispatches so Behat steps can
 * assert on the body (links, subject, etc.). The `null://null` mailer
 * transport in `when@test` drops the message, but `MessageEvent` still
 * fires before that — so we get the fully-built `Email` object.
 *
 * Storage is static because the SymfonyExtension Mink driver reboots the
 * kernel between requests, so a per-instance array would be wiped before
 * the assertion step runs.
 */
final class MailCollector implements EventSubscriberInterface
{
    /** @var list<Email> */
    private static array $messages = [];

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [MessageEvent::class => 'onMessage'];
    }

    public function onMessage(MessageEvent $event): void
    {
        $message = $event->getMessage();
        if ($message instanceof Email) {
            self::$messages[] = $message;
        }
    }

    public function reset(): void
    {
        self::$messages = [];
    }

    public function last(): ?Email
    {
        return self::$messages === [] ? null : self::$messages[array_key_last(self::$messages)];
    }
}
