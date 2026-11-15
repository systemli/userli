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
        $form = $this->factory->create(CustomAliasCreateType::class, null, ['domain' => 'example.org']);

        $form->submit(['alias' => 'testalias']);

        self::assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        self::assertEquals('testalias@example.org', $data->getAlias());
    }

    public function testClientSuppliedDomainIsIgnored(): void
    {
        $form = $this->factory->create(CustomAliasCreateType::class, null, ['domain' => 'example.org']);

        $form->submit(['alias' => 'testalias', 'domain' => 'example.com']);

        self::assertFalse($form->has('domain'));

        /** @var AliasCreate $data */
        $data = $form->getData();
        self::assertEquals('testalias@example.org', $data->getAlias());
    }

    public function testAliasIsLowercased(): void
    {
        $form = $this->factory->create(CustomAliasCreateType::class, null, ['domain' => 'example.org']);

        $form->submit(['alias' => 'TestAlias']);

        self::assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        self::assertEquals('testalias@example.org', $data->getAlias());
    }
}
