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
        $contentType = $request->getContentTypeFormat();
        $acceptableContentTypes = $request->getAcceptableContentTypes();

        if (
            str_contains($contentType, "application/json")
            and ((in_array("application/json", $acceptableContentTypes)) or (in_array("*/*", $acceptableContentTypes)))
        ) {
            $exception = $event->getThrowable();
            $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            $event->setResponse(new JsonResponse(['error' => $exception->getMessage()], $statusCode));
        }
    }
}
