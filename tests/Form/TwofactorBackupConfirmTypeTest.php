<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\TwofactorBackupConfirm;
use App\Form\TwofactorBackupConfirmType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class TwofactorBackupConfirmTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $formData = ['confirm' => true];

        $model = new TwofactorBackupConfirm();
        $form = $this->factory->create(TwofactorBackupConfirmType::class, $model);

        $expected = new TwofactorBackupConfirm();
        $expected->setConfirm(true);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $model);
    }

    public function testSubmitWithoutConfirm(): void
    {
        $formData = [];

        $model = new TwofactorBackupConfirm();
        $form = $this->factory->create(TwofactorBackupConfirmType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($model->isConfirm());
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(TwofactorBackupConfirmType::class);
        $view = $form->createView();

        self::assertArrayHasKey('confirm', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(TwofactorBackupConfirmType::class);

        self::assertSame('twofactor_backup_confirm', $form->getConfig()->getName());
    }
}
