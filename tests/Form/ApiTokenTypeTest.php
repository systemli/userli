<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Enum\ApiScope;
use App\Form\ApiTokenType;
use App\Form\Model\ApiToken;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class ApiTokenTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $name = 'My API Token';
        $scopes = [ApiScope::KEYCLOAK->value];

        $formData = [
            'name' => $name,
            'scopes' => $scopes,
        ];

        $model = new ApiToken();
        $form = $this->factory->create(ApiTokenType::class, $model);

        $expected = new ApiToken();
        $expected->setName($name);
        $expected->setScopes($scopes);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $model);
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(ApiTokenType::class);
        $view = $form->createView();

        self::assertArrayHasKey('name', $view->children);
        self::assertArrayHasKey('scopes', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testScopesFieldIsExpandedAndMultiple(): void
    {
        $form = $this->factory->create(ApiTokenType::class);

        $scopesConfig = $form->get('scopes')->getConfig();
        self::assertTrue($scopesConfig->getOption('expanded'));
        self::assertTrue($scopesConfig->getOption('multiple'));
    }
}
