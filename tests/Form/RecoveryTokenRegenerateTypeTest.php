<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\RecoveryTokenRegenerate;
use App\Form\RecoveryTokenRegenerateType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class RecoveryTokenRegenerateTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testOmitsTotpFieldByDefault(): void
    {
        $form = $this->factory->create(RecoveryTokenRegenerateType::class);

        $children = $form->createView()->children;

        self::assertArrayHasKey('password', $children);
        self::assertArrayHasKey('submit', $children);
        self::assertArrayNotHasKey('totpCode', $children);
    }

    public function testIncludesTotpFieldWhenRequested(): void
    {
        $form = $this->factory->create(RecoveryTokenRegenerateType::class, null, [
            'requires_totp' => true,
        ]);

        $children = $form->createView()->children;

        self::assertArrayHasKey('totpCode', $children);
        self::assertSame('form.twofactor-code', $children['totpCode']->vars['label']);
    }

    public function testSubmitPasswordOnlyData(): void
    {
        $form = $this->factory->create(RecoveryTokenRegenerateType::class);

        $form->submit(['password' => 's3cret']);

        self::assertTrue($form->isSynchronized());
        $data = $form->getData();
        self::assertInstanceOf(RecoveryTokenRegenerate::class, $data);
        self::assertSame('s3cret', $data->getPassword());
        self::assertNull($data->getTotpCode());
    }

    public function testSubmitWithTotpBindsBothFields(): void
    {
        $form = $this->factory->create(RecoveryTokenRegenerateType::class, null, [
            'requires_totp' => true,
        ]);

        $form->submit([
            'password' => 's3cret',
            'totpCode' => '123456',
        ]);

        self::assertTrue($form->isSynchronized());
        $data = $form->getData();
        self::assertInstanceOf(RecoveryTokenRegenerate::class, $data);
        self::assertSame('s3cret', $data->getPassword());
        self::assertSame('123456', $data->getTotpCode());
    }
}
