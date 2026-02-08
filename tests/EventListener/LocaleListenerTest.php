<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\LocaleListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class LocaleListenerTest extends TestCase
{
    private LocaleListener $listener;
    private Session $session;
    private Request $request;
    private RequestEvent $event;

    protected function setUp(): void
    {
        $this->listener = new LocaleListener('en', ['de', 'en']);

        $this->session = $this->createStub(Session::class);
        $this->request = $this->createMock(Request::class);
        $attributes = $this->createStub(ParameterBag::class);
        $attributes->method('getBoolean')->willReturn(false);
        $this->request->attributes = $attributes;
        $this->request->method('getSession')
            ->willReturn($this->session);
        $this->request->query = new InputBag();
        $this->event = $this->createStub(RequestEvent::class);
        $this->event->method('getRequest')
            ->willReturn($this->request);
    }

    public function testSupportedQueryLocale(): void
    {
        $this->session->method('get')
            ->willReturn(null);
        $this->request->query->set('_locale', 'de');
        $this->request->expects($this->once())->method('setLocale')->with('de');
        $this->listener->onKernelRequest($this->event);
    }

    public function testUnsupportedQueryLocale(): void
    {
        $this->session->method('get')
            ->willReturn(null);
        $this->request->query->set('_locale', 'xx');
        $this->request->expects($this->once())->method('setLocale')->with('en');
        $this->listener->onKernelRequest($this->event);
    }

    public function testSupportedBrowserLocale(): void
    {
        $this->session->method('get')
            ->willReturn(null);
        $this->request->method('getPreferredLanguage')
            ->willReturn('de');
        $this->request->expects($this->once())->method('setLocale')->with('de');
        $this->listener->onKernelRequest($this->event);
    }

    public function testUnsupportedBrowserLocale(): void
    {
        $this->session->method('get')
            ->willReturn(null);
        $this->request->method('getPreferredLanguage')
            ->willReturn(null);
        $this->request->expects($this->once())->method('setLocale')->with('en');
        $this->listener->onKernelRequest($this->event);
    }

    public function testSessionLocale(): void
    {
        $this->session->method('get')
            ->willReturn('de');
        $this->request->expects($this->once())->method('setLocale')->with('de');
        $this->listener->onKernelRequest($this->event);
    }
}
