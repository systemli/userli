<?php

namespace App\Tests\Helper;

use App\Helper\JsonRequestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class JsonRequestHelperTest extends TestCase
{
    public function testWantsJsonWithApiPath(): void
    {
        $request = Request::create('/api/v1/users', 'GET');
        $this->assertTrue(JsonRequestHelper::wantsJson($request));
    }

    public function testWantsJsonWithNestedApiPath(): void
    {
        $request = Request::create('/api/admin/users', 'GET');
        $this->assertTrue(JsonRequestHelper::wantsJson($request));
    }

    public function testWantsJsonWithJsonAccept(): void
    {
        $request = Request::create(uri: '/some/path', server: ['HTTP_ACCEPT' => 'application/json']);
        $this->assertTrue(JsonRequestHelper::wantsJson($request));
    }

    public function testWantsJsonWithJsonContentType(): void
    {
        $request = Request::create(uri: '/some/path', server: ['CONTENT_TYPE' => 'application/json']);
        $this->assertTrue(JsonRequestHelper::wantsJson($request));
    }

    public function testWantsJsonWithJsonFormat(): void
    {
        $request = Request::create('/some/path');
        $request->setRequestFormat('json');
        $this->assertTrue(JsonRequestHelper::wantsJson($request));
    }

    public function testDoesNotWantJsonWithRegularPath(): void
    {
        $request = Request::create('/regular/page');
        $this->assertFalse(JsonRequestHelper::wantsJson($request));
    }

    public function testDoesNotWantJsonWithHtmlAccept(): void
    {
        $request = Request::create(uri: '/some/path', server: ['HTTP_ACCEPT' => 'text/html']);
        $this->assertFalse(JsonRequestHelper::wantsJson($request));
    }
}
