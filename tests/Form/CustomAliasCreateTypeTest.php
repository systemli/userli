<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\CustomAliasCreateType;
use App\Form\Model\AliasCreate;
use Symfony\Component\Form\Test\TypeTestCase;

class CustomAliasCreateTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $localPart = 'testalias';
        $domain = 'example.org';
        $formData = ['alias' => $localPart, 'domain' => $domain];

        $form = $this->factory->create(CustomAliasCreateType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        // alias now contains the full email
        $this->assertEquals($localPart.'@'.$domain, $data->getAlias());
    }

    public function testSubmitWithoutDomainKeepsLocalPart(): void
    {
        $localPart = 'testalias';
        $formData = ['alias' => $localPart];

        $form = $this->factory->create(CustomAliasCreateType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        // Without domain, alias stays as local part only
        $this->assertEquals($localPart, $data->getAlias());
    }

    public function testAliasIsLowercased(): void
    {
        $localPart = 'TestAlias';
        $domain = 'example.org';
        $formData = ['alias' => $localPart, 'domain' => $domain];

        $form = $this->factory->create(CustomAliasCreateType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        $this->assertEquals('testalias@'.$domain, $data->getAlias());
    }

    public function testDomainFieldIsUnmapped(): void
    {
        $form = $this->factory->create(CustomAliasCreateType::class);

        $domainField = $form->get('domain');
        $this->assertFalse($domainField->getConfig()->getMapped());
    }
}
