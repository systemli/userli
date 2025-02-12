<?php

namespace App\Tests\EventListener;

use App\EventListener\JsonResponseExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class JsonResponseExceptionListenerTest extends TestCase
{
    public function testOnKernelException()
    {
        $listener = new JsonResponseExceptionListener();

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');

        $exception = new HttpException(400, 'Bad Request');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $listener->onKernelException($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Bad Request', $data['error']);
    }
}
