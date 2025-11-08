<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\ApiToken;
use App\Security\ApiTokenAuthenticator;
use App\Security\Badge\ApiTokenBadge;
use App\Service\ApiTokenManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticatorTest extends TestCase
{
    private ApiTokenManager $apiTokenManager;
    private ApiTokenAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->apiTokenManager = $this->createMock(ApiTokenManager::class);
        $this->authenticator = new ApiTokenAuthenticator($this->apiTokenManager);
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(string $pathInfo, bool $expected): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', $pathInfo);

        $this->assertEquals($expected, $this->authenticator->supports($request));
    }

    public static function supportsProvider(): array
    {
        return [
            'API path' => ['/api/test', true],
            'API path with sub-path' => ['/api/users/123', true],
            'API root path' => ['/api/', true],
            'Non-API path' => ['/user/settings', false],
            'Root path' => ['/', false],
            'Path starting with api but not API' => ['/application/test', false],
            'Empty path' => ['', false],
        ];
    }

    public function testAuthenticateWithBearerTokenSuccess(): void
    {
        $plainToken = 'test-token-123';
        $apiToken = $this->createApiToken();

        $request = new Request();
        $request->headers->set('Authorization', 'Bearer '.$plainToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('findOne')
            ->with($plainToken)
            ->willReturn($apiToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('updateLastUsedTime')
            ->with($apiToken);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $this->assertInstanceOf(UserBadge::class, $passport->getBadge(UserBadge::class));
        $this->assertEquals('api_user', $passport->getBadge(UserBadge::class)->getUserIdentifier());

        $apiTokenBadge = $passport->getBadge(ApiTokenBadge::class);
        $this->assertInstanceOf(ApiTokenBadge::class, $apiTokenBadge);
        $this->assertSame($apiToken, $apiTokenBadge->getApiToken());

        $this->assertSame($apiToken, $request->attributes->get('api_token'));
    }

    public function testAuthenticateWithXApiTokenHeaderSuccess(): void
    {
        $plainToken = 'test-token-456';
        $apiToken = $this->createApiToken();

        $request = new Request();
        $request->headers->set('X-API-Token', $plainToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('findOne')
            ->with($plainToken)
            ->willReturn($apiToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('updateLastUsedTime')
            ->with($apiToken);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $this->assertSame($apiToken, $request->attributes->get('api_token'));
    }

    public function testAuthenticateWithBearerTokenCaseInsensitive(): void
    {
        $plainToken = 'test-token-789';
        $apiToken = $this->createApiToken();

        $request = new Request();
        $request->headers->set('Authorization', 'bearer '.$plainToken); // lowercase

        $this->apiTokenManager
            ->expects($this->once())
            ->method('findOne')
            ->with($plainToken)
            ->willReturn($apiToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('updateLastUsedTime')
            ->with($apiToken);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $this->assertSame($apiToken, $request->attributes->get('api_token'));
    }

    public function testAuthenticateWithBearerTokenPrecedenceOverXApiToken(): void
    {
        $bearerToken = 'bearer-token';
        $xApiToken = 'x-api-token';
        $apiToken = $this->createApiToken();

        $request = new Request();
        $request->headers->set('Authorization', 'Bearer '.$bearerToken);
        $request->headers->set('X-API-Token', $xApiToken);

        // Should use Bearer token, not X-API-Token
        $this->apiTokenManager
            ->expects($this->once())
            ->method('findOne')
            ->with($bearerToken)
            ->willReturn($apiToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('updateLastUsedTime')
            ->with($apiToken);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateWithNoTokenThrowsException(): void
    {
        $request = new Request();

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No API token provided');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateWithEmptyBearerTokenThrowsException(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer ');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No API token provided');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateWithEmptyXApiTokenThrowsException(): void
    {
        $request = new Request();
        $request->headers->set('X-API-Token', '');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No API token provided');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateWithInvalidBearerFormatFallsBackToXApiToken(): void
    {
        $xApiToken = 'valid-x-api-token';
        $apiToken = $this->createApiToken();

        $request = new Request();
        $request->headers->set('Authorization', 'InvalidFormat token123');
        $request->headers->set('X-API-Token', $xApiToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('findOne')
            ->with($xApiToken)
            ->willReturn($apiToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('updateLastUsedTime')
            ->with($apiToken);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateWithInvalidTokenThrowsException(): void
    {
        $plainToken = 'invalid-token';

        $request = new Request();
        $request->headers->set('Authorization', 'Bearer '.$plainToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('findOne')
            ->with($plainToken)
            ->willReturn(null);

        $this->apiTokenManager
            ->expects($this->never())
            ->method('updateLastUsedTime');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid API token');

        $this->authenticator->authenticate($request);
    }

    public function testOnAuthenticationSuccessReturnsNull(): void
    {
        $request = new Request();
        $token = $this->createMock(TokenInterface::class);

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'api');

        $this->assertNull($response);
    }

    public function testOnAuthenticationFailureReturnsJsonResponse(): void
    {
        $request = new Request();
        $exception = new AuthenticationException('Custom error message');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('Custom error message', $content['error']);
    }

    public function testOnAuthenticationFailureWithCustomUserMessageException(): void
    {
        $request = new Request();
        $exception = new CustomUserMessageAuthenticationException('No API token provided');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('No API token provided', $content['error']);
    }

    /**
     * @dataProvider extractTokenProvider
     */
    public function testExtractToken(array $headers, ?string $expectedToken): void
    {
        $request = new Request();
        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }

        // Use reflection to access private method
        $reflection = new ReflectionClass($this->authenticator);
        $method = $reflection->getMethod('extractToken');
        $method->setAccessible(true);

        $result = $method->invoke($this->authenticator, $request);

        $this->assertEquals($expectedToken, $result);
    }

    public static function extractTokenProvider(): array
    {
        return [
            'Bearer token' => [
                ['Authorization' => 'Bearer abc123'],
                'abc123',
            ],
            'Bearer token with extra spaces' => [
                ['Authorization' => 'Bearer   token-with-spaces   '],
                'token-with-spaces   ',
            ],
            'Bearer token case insensitive' => [
                ['Authorization' => 'bearer token123'],
                'token123',
            ],
            'X-API-Token header' => [
                ['X-API-Token' => 'xyz789'],
                'xyz789',
            ],
            'Both headers - Bearer takes precedence' => [
                [
                    'Authorization' => 'Bearer priority-token',
                    'X-API-Token' => 'fallback-token',
                ],
                'priority-token',
            ],
            'Invalid Authorization format falls back to X-API-Token' => [
                [
                    'Authorization' => 'Basic username:password',
                    'X-API-Token' => 'fallback-token',
                ],
                'fallback-token',
            ],
            'No token headers' => [
                [],
                null,
            ],
            'Empty Authorization header' => [
                ['Authorization' => ''],
                null,
            ],
            'Empty X-API-Token header' => [
                ['X-API-Token' => ''],
                '',
            ],
            'Authorization without Bearer' => [
                ['Authorization' => 'sometoken'],
                null,
            ],
        ];
    }

    public function testCompleteAuthenticationFlow(): void
    {
        $plainToken = 'complete-flow-token';
        $apiToken = $this->createApiToken();

        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');
        $request->headers->set('Authorization', 'Bearer '.$plainToken);

        // Test supports
        $this->assertTrue($this->authenticator->supports($request));

        // Mock API token manager
        $this->apiTokenManager
            ->expects($this->once())
            ->method('findOne')
            ->with($plainToken)
            ->willReturn($apiToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('updateLastUsedTime')
            ->with($apiToken);

        // Test authenticate
        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $this->assertSame($apiToken, $request->attributes->get('api_token'));

        // Test success callback
        $token = $this->createMock(TokenInterface::class);
        $successResponse = $this->authenticator->onAuthenticationSuccess($request, $token, 'api');
        $this->assertNull($successResponse);
    }

    private function createApiToken(): ApiToken
    {
        return $this->getMockBuilder(ApiToken::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
