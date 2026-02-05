<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\TwofactorBackupConfirm;
use App\Form\TwofactorBackupConfirmType;
use Symfony\Component\Form\Test\TypeTestCase;

class TwofactorBackupConfirmTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = ['confirm' => true];

        $model = new TwofactorBackupConfirm();
        $form = $this->factory->create(TwofactorBackupConfirmType::class, $model);

        $expected = new TwofactorBackupConfirm();
        $expected->setConfirm(true);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);
    }

    public function testSubmitWithoutConfirm(): void
    {
        $formData = [];

        $model = new TwofactorBackupConfirm();
        $form = $this->factory->create(TwofactorBackupConfirmType::class, $model);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($model->isConfirm());
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(TwofactorBackupConfirmType::class);
        $view = $form->createView();

        $this->assertArrayHasKey('confirm', $view->children);
        $this->assertArrayHasKey('submit', $view->children);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(TwofactorBackupConfirmType::class);

        $this->assertSame('twofactor_backup_confirm', $form->getConfig()->getName());
    }
}
