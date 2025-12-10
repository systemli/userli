<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\ApiToken;
use App\Security\RequireApiScope;
use Override;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ApiScopeListener implements EventSubscriberInterface
{
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            ControllerArgumentsEvent::class => 'onKernelControllerArguments',
        ];
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $controller = $event->getController();
        if (!is_array($controller)) {
            return;
        }

        [$controllerObject, $methodName] = $controller;

        $apiToken = $request->attributes->get('api_token');
        if (!$apiToken instanceof ApiToken) {
            return;
        }

        $reflectionClass = new ReflectionClass($controllerObject);
        $reflectionMethod = $reflectionClass->getMethod($methodName);

        $attribute = null;
        $methodAttributes = $reflectionMethod->getAttributes(RequireApiScope::class);
        if (!empty($methodAttributes)) {
            $attribute = $methodAttributes[0]->newInstance();
        } else {
            $classAttributes = $reflectionClass->getAttributes(RequireApiScope::class);
            if (!empty($classAttributes)) {
                $attribute = $classAttributes[0]->newInstance();
            }
        }

        if ($attribute && !in_array($attribute->scope->value, $apiToken->getScopes(), true)) {
            throw new AccessDeniedHttpException(sprintf('Token does not have required scope: %s', $attribute->scope->value));
        }
    }
}
