<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Helper\JsonRequestHelper;
use Override;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Converts exceptions into JSON error responses for API requests. Includes stack traces in dev environment.
 */
final readonly class JsonExceptionListener implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('kernel.environment')]
        private string $environment,
    ) {
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!JsonRequestHelper::wantsJson($request)) {
            return;
        }

        $exception = $event->getThrowable();

        $data = [
            'error' => [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ],
        ];

        $statusCode = 500;
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        if ($this->environment === 'dev') {
            $data['error']['exception'] = $exception::class;
            $data['error']['file'] = $exception->getFile();
            $data['error']['line'] = $exception->getLine();
            $data['error']['trace'] = $exception->getTrace();
        }

        $response = new JsonResponse($data, $statusCode);
        $event->setResponse($response);
    }
}
