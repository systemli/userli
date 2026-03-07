<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Domain;
use App\Event\AliasEvent;
use App\Helper\RandomStringGenerator;
use App\Repository\AliasRepository;
use App\Sender\AliasCreatedMessageSender;
use Exception;
use Override;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class AliasListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $request,
        private AliasCreatedMessageSender $sender,
        private AliasRepository $aliasRepository,
        #[Autowire('kernel.default_locale')] private string $defaultLocale,
    ) {
    }

    /**
     * @throws Exception
     */
    public function onCustomCreated(AliasEvent $event): void
    {
        $alias = $event->getAlias();

        if (null === ($user = $alias->getUser())) {
            throw new Exception('User should not be null');
        }

        $locale = $this->request->getSession()->get('_locale', $this->defaultLocale);
        $this->sender->send($user, $alias, $locale);
    }

    public function onRandomCreated(AliasEvent $event): void
    {
        $alias = $event->getAlias();

        while (null !== $this->aliasRepository->findOneBySource($alias->getSource(), true)) {
            $localPart = RandomStringGenerator::generate(24, false);
            /** @var Domain $domain */
            $domain = $alias->getDomain();
            $alias->setSource($localPart.'@'.$domain->getName());
        }
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            AliasEvent::CUSTOM_CREATED => 'onCustomCreated',
            AliasEvent::RANDOM_CREATED => 'onRandomCreated',
        ];
    }
}
