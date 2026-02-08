<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\JsonRequestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class JsonRequestHelperTest extends TestCase
{
    public function testWantsJsonWithApiPath(): void
    {
        $request = Request::create('/api/v1/users', 'GET');
        self::assertTrue(JsonRequestHelper::wantsJson($request));
    }

    public function testWantsJsonWithNestedApiPath(): void
    {
        $request = Request::create('/api/admin/users', 'GET');
        self::assertTrue(JsonRequestHelper::wantsJson($request));
    }

    public function testWantsJsonWithJsonAccept(): void
    {
        $request = Request::create(uri: '/some/path', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertTrue(JsonRequestHelper::wantsJson($request));
    }

    public function testWantsJsonWithJsonContentType(): void
    {
        $request = Request::create(uri: '/some/path', server: ['CONTENT_TYPE' => 'application/json']);
        self::assertTrue(JsonRequestHelper::wantsJson($request));
    }

    public function testWantsJsonWithJsonFormat(): void
    {
        $request = Request::create('/some/path');
        $request->setRequestFormat('json');
        self::assertTrue(JsonRequestHelper::wantsJson($request));
    }

    public function testDoesNotWantJsonWithRegularPath(): void
    {
        $request = Request::create('/regular/page');
        self::assertFalse(JsonRequestHelper::wantsJson($request));
    }

    public function testDoesNotWantJsonWithHtmlAccept(): void
    {
        $request = Request::create(uri: '/some/path', server: ['HTTP_ACCEPT' => 'text/html']);
        self::assertFalse(JsonRequestHelper::wantsJson($request));
    }
}
