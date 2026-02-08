<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\JsonExceptionListener;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

class JsonExceptionListenerTest extends TestCase
{
    private JsonExceptionListener $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new JsonExceptionListener('prod');
    }

    public function testGetSubscribedEvents(): void
    {
        $events = JsonExceptionListener::getSubscribedEvents();

        self::assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        self::assertEquals(['onKernelException', 0], $events[KernelEvents::EXCEPTION]);
    }

    public function testOnKernelExceptionWithApiRequest(): void
    {
        $request = Request::create('/api/users', 'GET');
        $exception = new Exception('Test exception', 123);
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(500, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $data);
        self::assertEquals('Test exception', $data['error']['message']);
        self::assertEquals(123, $data['error']['code']);
        self::assertArrayNotHasKey('exception', $data['error']);
        self::assertArrayNotHasKey('file', $data['error']);
        self::assertArrayNotHasKey('line', $data['error']);
        self::assertArrayNotHasKey('trace', $data['error']);
    }

    public function testOnKernelExceptionWithJsonAcceptHeader(): void
    {
        $request = Request::create(uri: '/some/path', server: ['HTTP_ACCEPT' => 'application/json']);
        $exception = new Exception('Test exception');
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
    }

    public function testOnKernelExceptionWithJsonRequestFormat(): void
    {
        $request = Request::create('/some/path', 'GET');
        $request->setRequestFormat('json');
        $exception = new Exception('Test exception');
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
    }

    public function testOnKernelExceptionWithHttpException(): void
    {
        $request = Request::create('/api/users', 'GET');
        $exception = new NotFoundHttpException('Resource not found');
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertEquals('Resource not found', $data['error']['message']);
    }

    public function testOnKernelExceptionWithCustomHttpException(): void
    {
        $request = Request::create('/api/users', 'GET');
        $exception = new HttpException(422, 'Validation failed', null, [], 1001);
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertEquals('Validation failed', $data['error']['message']);
        self::assertEquals(1001, $data['error']['code']);
    }

    public function testOnKernelExceptionInDevEnvironment(): void
    {
        $devSubscriber = new JsonExceptionListener('dev');
        $request = Request::create('/api/users', 'GET');
        $exception = new Exception('Test exception', 123);
        $event = $this->createExceptionEvent($request, $exception);

        $devSubscriber->onKernelException($event);

        $response = $event->getResponse();
        $data = json_decode($response->getContent(), true);

        self::assertArrayHasKey('exception', $data['error']);
        self::assertArrayHasKey('file', $data['error']);
        self::assertArrayHasKey('line', $data['error']);
        self::assertArrayHasKey('trace', $data['error']);

        self::assertEquals(Exception::class, $data['error']['exception']);
        self::assertIsString($data['error']['file']);
        self::assertIsInt($data['error']['line']);
        self::assertIsArray($data['error']['trace']);
    }

    public function testOnKernelExceptionWithNonJsonRequest(): void
    {
        $request = Request::create('/regular/page', 'GET');
        $exception = new Exception('Test exception');
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelExceptionWithHtmlRequest(): void
    {
        $request = Request::create(uri: '/regular/page', server: ['HTTP_ACCEPT' => 'text/html']);
        $exception = new Exception('Test exception');
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testJsonResponseStructure(): void
    {
        $request = Request::create('/api/users', 'GET');
        $exception = new Exception('Test message', 42);
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertIsArray($data['error']);
        self::assertArrayHasKey('message', $data['error']);
        self::assertArrayHasKey('code', $data['error']);
    }

    private function createExceptionEvent(Request $request, Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }
}
