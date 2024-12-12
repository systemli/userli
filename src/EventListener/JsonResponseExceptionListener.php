<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class JsonResponseExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $request = $event->getRequest();

        if (
            $request->headers->get('Accept') === 'application/json' ||
            $request->headers->get('Content-Type') === 'application/json'
        ) {
            $exception = $event->getThrowable();
            $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            $event->setResponse(new JsonResponse(['error' => $exception->getMessage()], $statusCode));
        }
    }
}
