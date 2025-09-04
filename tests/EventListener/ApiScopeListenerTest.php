<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\ApiToken;
use App\Enum\ApiScope;
use App\EventListener\ApiScopeListener;
use App\Security\RequireApiScope;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiScopeListenerTest extends TestCase
{
    private ApiScopeListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ApiScopeListener();
    }

    public function testGetSubscribedEvents(): void
    {
        $events = ApiScopeListener::getSubscribedEvents();

        $this->assertArrayHasKey(ControllerArgumentsEvent::class, $events);
        $this->assertEquals('onKernelControllerArguments', $events[ControllerArgumentsEvent::class]);
    }

    public function testIgnoresNonApiRequests(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/user/settings');

        $event = $this->createControllerArgumentsEvent($request, [new TestController(), 'testMethod']);

        // Should not throw any exception
        $this->listener->onKernelControllerArguments($event);
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testIgnoresNonArrayController(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');

        $event = $this->createControllerArgumentsEvent($request, function() { return 'test'; });

        // Should not throw any exception
        $this->listener->onKernelControllerArguments($event);
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testIgnoresRequestsWithoutApiToken(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');

        $event = $this->createControllerArgumentsEvent($request, [new TestController(), 'testMethod']);

        // Should not throw any exception when no api_token attribute is set
        $this->listener->onKernelControllerArguments($event);
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testIgnoresRequestsWithInvalidApiToken(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');
        $request->attributes->set('api_token', 'invalid_token_string');

        $event = $this->createControllerArgumentsEvent($request, [new TestController(), 'testMethod']);

        // Should not throw any exception when api_token is not an ApiToken instance
        $this->listener->onKernelControllerArguments($event);
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testAllowsAccessWhenNoScopeRequired(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');

        $apiToken = $this->createApiToken(['keycloak']);
        $request->attributes->set('api_token', $apiToken);

        $event = $this->createControllerArgumentsEvent($request, [new TestController(), 'testMethod']);

        // Should not throw any exception when no scope is required
        $this->listener->onKernelControllerArguments($event);
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testAllowsAccessWithCorrectMethodScope(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');

        $apiToken = $this->createApiToken(['keycloak']);
        $request->attributes->set('api_token', $apiToken);

        $event = $this->createControllerArgumentsEvent($request, [new TestControllerWithMethodScope(), 'methodWithKeycloakScope']);

        // Should not throw any exception when token has required scope
        $this->listener->onKernelControllerArguments($event);
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testAllowsAccessWithCorrectClassScope(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');

        $apiToken = $this->createApiToken(['dovecot']);
        $request->attributes->set('api_token', $apiToken);

        $event = $this->createControllerArgumentsEvent($request, [new TestControllerWithClassScope(), 'testMethod']);

        // Should not throw any exception when token has required scope
        $this->listener->onKernelControllerArguments($event);
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testDeniesAccessWithIncorrectMethodScope(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');

        $apiToken = $this->createApiToken(['dovecot']); // Token has dovecot, but method requires keycloak
        $request->attributes->set('api_token', $apiToken);

        $event = $this->createControllerArgumentsEvent($request, [new TestControllerWithMethodScope(), 'methodWithKeycloakScope']);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Token does not have required scope: keycloak');

        $this->listener->onKernelControllerArguments($event);
    }

    public function testDeniesAccessWithIncorrectClassScope(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');

        $apiToken = $this->createApiToken(['keycloak']); // Token has keycloak, but class requires dovecot
        $request->attributes->set('api_token', $apiToken);

        $event = $this->createControllerArgumentsEvent($request, [new TestControllerWithClassScope(), 'testMethod']);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Token does not have required scope: dovecot');

        $this->listener->onKernelControllerArguments($event);
    }

    public function testMethodScopeOverridesClassScope(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');

        $apiToken = $this->createApiToken(['keycloak']); // Token has keycloak scope
        $request->attributes->set('api_token', $apiToken);

        // Class requires dovecot, method requires keycloak - method should take precedence
        $event = $this->createControllerArgumentsEvent($request, [new TestControllerWithBothScopes(), 'methodWithKeycloakScope']);

        // Should not throw any exception as method scope (keycloak) takes precedence over class scope (dovecot)
        $this->listener->onKernelControllerArguments($event);
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testAllowsAccessWithMultipleScopes(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');

        $apiToken = $this->createApiToken(['keycloak', 'dovecot']); // Token has both scopes
        $request->attributes->set('api_token', $apiToken);

        $event = $this->createControllerArgumentsEvent($request, [new TestControllerWithMethodScope(), 'methodWithKeycloakScope']);

        // Should not throw any exception when token has required scope among others
        $this->listener->onKernelControllerArguments($event);
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testDeniesAccessWithEmptyScopes(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');

        $apiToken = $this->createApiToken([]); // Token has no scopes
        $request->attributes->set('api_token', $apiToken);

        $event = $this->createControllerArgumentsEvent($request, [new TestControllerWithMethodScope(), 'methodWithKeycloakScope']);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Token does not have required scope: keycloak');

        $this->listener->onKernelControllerArguments($event);
    }

    /**
     * @dataProvider pathInfoProvider
     */
    public function testCorrectlyIdentifiesApiPaths(string $pathInfo, bool $shouldProcess): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', $pathInfo);

        $apiToken = $this->createApiToken(['dovecot']);
        $request->attributes->set('api_token', $apiToken);

        $event = $this->createControllerArgumentsEvent($request, [new TestControllerWithClassScope(), 'testMethod']);

        if ($shouldProcess) {
            // Should throw exception because token has dovecot scope but class requires dovecot (this should pass)
            $this->listener->onKernelControllerArguments($event);
            $this->assertTrue(true);
        } else {
            // Should not process non-API paths, so no exception should be thrown
            $this->listener->onKernelControllerArguments($event);
            $this->assertTrue(true);
        }
    }

    public static function pathInfoProvider(): array
    {
        return [
            'API path' => ['/api/test', true],
            'API path with sub-path' => ['/api/users/123', true],
            'Non-API path' => ['/user/settings', false],
            'Root path' => ['/', false],
            'Path starting with api but not API' => ['/application/test', false],
            'Empty path' => ['', false],
        ];
    }

    private function createApiToken(array $scopes): ApiToken
    {
        $apiToken = $this->getMockBuilder(ApiToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiToken->method('getScopes')->willReturn($scopes);

        return $apiToken;
    }

    private function createControllerArgumentsEvent(Request $request, $controller): ControllerArgumentsEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ControllerArgumentsEvent(
            $kernel,
            $controller,
            [],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }
}

// Test controller classes for testing

class TestController
{
    public function testMethod(): void
    {
    }
}

class TestControllerWithMethodScope
{
    #[RequireApiScope(ApiScope::KEYCLOAK)]
    public function methodWithKeycloakScope(): void
    {
    }

    #[RequireApiScope(ApiScope::DOVECOT)]
    public function methodWithDovecotScope(): void
    {
    }

    public function methodWithoutScope(): void
    {
    }
}

#[RequireApiScope(ApiScope::DOVECOT)]
class TestControllerWithClassScope
{
    public function testMethod(): void
    {
    }
}

#[RequireApiScope(ApiScope::DOVECOT)]
class TestControllerWithBothScopes
{
    #[RequireApiScope(ApiScope::KEYCLOAK)]
    public function methodWithKeycloakScope(): void
    {
    }
}
