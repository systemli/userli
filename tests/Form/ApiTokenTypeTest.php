<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Enum\ApiScope;
use App\Form\ApiTokenType;
use App\Form\Model\ApiToken;
use Symfony\Component\Form\Test\TypeTestCase;

class ApiTokenTypeTest extends TypeTestCase
{
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

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(ApiTokenType::class);
        $view = $form->createView();

        $this->assertArrayHasKey('name', $view->children);
        $this->assertArrayHasKey('scopes', $view->children);
        $this->assertArrayHasKey('submit', $view->children);
    }

    public function testScopesFieldIsExpandedAndMultiple(): void
    {
        $form = $this->factory->create(ApiTokenType::class);

        $scopesConfig = $form->get('scopes')->getConfig();
        $this->assertTrue($scopesConfig->getOption('expanded'));
        $this->assertTrue($scopesConfig->getOption('multiple'));
    }
}
