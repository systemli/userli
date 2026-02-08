<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\CustomAliasCreateType;
use App\Form\Model\AliasCreate;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class CustomAliasCreateTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $localPart = 'testalias';
        $domain = 'example.org';
        $formData = ['alias' => $localPart, 'domain' => $domain];

        $form = $this->factory->create(CustomAliasCreateType::class);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        // alias now contains the full email
        self::assertEquals($localPart.'@'.$domain, $data->getAlias());
    }

    public function testSubmitWithoutDomainKeepsLocalPart(): void
    {
        $localPart = 'testalias';
        $formData = ['alias' => $localPart];

        $form = $this->factory->create(CustomAliasCreateType::class);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        // Without domain, alias stays as local part only
        self::assertEquals($localPart, $data->getAlias());
    }

    public function testAliasIsLowercased(): void
    {
        $localPart = 'TestAlias';
        $domain = 'example.org';
        $formData = ['alias' => $localPart, 'domain' => $domain];

        $form = $this->factory->create(CustomAliasCreateType::class);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        self::assertEquals('testalias@'.$domain, $data->getAlias());
    }

    public function testDomainFieldIsUnmapped(): void
    {
        $form = $this->factory->create(CustomAliasCreateType::class);

        $domainField = $form->get('domain');
        self::assertFalse($domainField->getConfig()->getMapped());
    }
}
