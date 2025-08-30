<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Helper\JsonRequestHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class PasswordChangeListener implements EventSubscriberInterface
{
    public function __construct(
        private Security              $security,
        private UrlGeneratorInterface $urlGenerator,
    )
    {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onRequest', 0]]
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        // Return if not the main request or the user is not fully authenticated
        if (!$event->isMainRequest() || !$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        $user = $this->security->getUser();
        // Return if the user is not an instance of User or does not require a password change
        if (!$user instanceof User || !$user->isPasswordChangeRequired()) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');
        // Return if the user accesses the password change page
        if (in_array($route, ['account_password', 'account_password_submit'], true)) {
            return;
        }

        // Deny access when trying to access API or JSON endpoints
        if (JsonRequestHelper::wantsJson($event->getRequest())) {
            throw new AccessDeniedHttpException("You must change your password before accessing other resources.");
        }

        // Redirect to the password change page
        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('account_password')));
    }
}
