<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\JsonExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class JsonExceptionSubscriberTest extends TestCase
{
    private JsonExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new JsonExceptionSubscriber('prod');
    }

    public function testGetSubscribedEvents(): void
    {
        $events = JsonExceptionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        $this->assertEquals(['onKernelException', 0], $events[KernelEvents::EXCEPTION]);
    }

    public function testOnKernelExceptionWithApiRequest(): void
    {
        $request = Request::create('/api/users', 'GET');
        $exception = new \Exception('Test exception', 123);
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Test exception', $data['error']['message']);
        $this->assertEquals(123, $data['error']['code']);
        $this->assertArrayNotHasKey('exception', $data['error']);
        $this->assertArrayNotHasKey('file', $data['error']);
        $this->assertArrayNotHasKey('line', $data['error']);
        $this->assertArrayNotHasKey('trace', $data['error']);
    }

    public function testOnKernelExceptionWithJsonAcceptHeader(): void
    {
        $request = Request::create(uri: '/some/path', server: ['HTTP_ACCEPT' => 'application/json']);
        $exception = new \Exception('Test exception');
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testOnKernelExceptionWithJsonRequestFormat(): void
    {
        $request = Request::create('/some/path', 'GET');
        $request->setRequestFormat('json');
        $exception = new \Exception('Test exception');
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testOnKernelExceptionWithHttpException(): void
    {
        $request = Request::create('/api/users', 'GET');
        $exception = new NotFoundHttpException('Resource not found');
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Resource not found', $data['error']['message']);
    }

    public function testOnKernelExceptionWithCustomHttpException(): void
    {
        $request = Request::create('/api/users', 'GET');
        $exception = new HttpException(422, 'Validation failed', null, [], 1001);
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Validation failed', $data['error']['message']);
        $this->assertEquals(1001, $data['error']['code']);
    }

    public function testOnKernelExceptionInDevEnvironment(): void
    {
        $devSubscriber = new JsonExceptionSubscriber('dev');
        $request = Request::create('/api/users', 'GET');
        $exception = new \Exception('Test exception', 123);
        $event = $this->createExceptionEvent($request, $exception);

        $devSubscriber->onKernelException($event);

        $response = $event->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('exception', $data['error']);
        $this->assertArrayHasKey('file', $data['error']);
        $this->assertArrayHasKey('line', $data['error']);
        $this->assertArrayHasKey('trace', $data['error']);

        $this->assertEquals(\Exception::class, $data['error']['exception']);
        $this->assertIsString($data['error']['file']);
        $this->assertIsInt($data['error']['line']);
        $this->assertIsArray($data['error']['trace']);
    }

    public function testOnKernelExceptionWithNonJsonRequest(): void
    {
        $request = Request::create('/regular/page', 'GET');
        $exception = new \Exception('Test exception');
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelExceptionWithHtmlRequest(): void
    {
        $request = Request::create(uri: '/regular/page', server: ['HTTP_ACCEPT' => 'text/html']);
        $exception = new \Exception('Test exception');
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testWantsJsonWithApiPath(): void
    {
        $request = Request::create('/api/v1/users', 'GET');
        $this->assertTrue($this->invokeWantsJson($request));
    }

    public function testWantsJsonWithNestedApiPath(): void
    {
        $request = Request::create('/api/admin/users', 'GET');
        $this->assertTrue($this->invokeWantsJson($request));
    }

    public function testWantsJsonWithJsonAccept(): void
    {
        $request = Request::create(uri: '/some/path', server: ['HTTP_ACCEPT' => 'application/json']);
        $this->assertTrue($this->invokeWantsJson($request));
    }

    public function testWantsJsonWithJsonContentType(): void
    {
        $request = Request::create(uri: '/some/path', server: ['CONTENT_TYPE' => 'application/json']);
        $this->assertTrue($this->invokeWantsJson($request));
    }

    public function testWantsJsonWithJsonFormat(): void
    {
        $request = Request::create('/some/path');
        $request->setRequestFormat('json');
        $this->assertTrue($this->invokeWantsJson($request));
    }

    public function testDoesNotWantJsonWithRegularPath(): void
    {
        $request = Request::create('/regular/page');
        $this->assertFalse($this->invokeWantsJson($request));
    }

    public function testDoesNotWantJsonWithHtmlAccept(): void
    {
        $request = Request::create(uri: '/some/path', server: ['HTTP_ACCEPT' => 'text/html']);
        $this->assertFalse($this->invokeWantsJson($request));
    }

    public function testJsonResponseStructure(): void
    {
        $request = Request::create('/api/users', 'GET');
        $exception = new \Exception('Test message', 42);
        $event = $this->createExceptionEvent($request, $exception);

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertIsArray($data['error']);
        $this->assertArrayHasKey('message', $data['error']);
        $this->assertArrayHasKey('code', $data['error']);
    }

    private function createExceptionEvent(Request $request, \Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }

    private function invokeWantsJson(Request $request): bool
    {
        $reflection = new \ReflectionClass(JsonExceptionSubscriber::class);
        $method = $reflection->getMethod('wantsJson');
        $method->setAccessible(true);

        return $method->invoke($this->subscriber, $request);
    }
}
