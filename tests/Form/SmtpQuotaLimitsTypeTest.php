<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\SmtpQuotaLimitsType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class SmtpQuotaLimitsTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'per_hour' => 100,
            'per_day' => 1000,
        ];

        $form = $this->factory->create(SmtpQuotaLimitsType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $expected = [
            'per_hour' => 100,
            'per_day' => 1000,
        ];

        $this->assertEquals($expected, $form->getData());
    }

    public function testSubmitPartialData(): void
    {
        $formData = [
            'per_hour' => 5,
        ];

        $form = $this->factory->create(SmtpQuotaLimitsType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $expected = [
            'per_hour' => 5,
            'per_day' => null,
        ];

        $this->assertEquals($expected, $form->getData());
    }

    public function testSubmitEmptyData(): void
    {
        $formData = [];

        $form = $this->factory->create(SmtpQuotaLimitsType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $expected = [
            'per_hour' => null,
            'per_day' => null,
        ];

        $this->assertEquals($expected, $form->getData());
    }

    public function testFormHasExpectedFields(): void
    {
        $form = $this->factory->create(SmtpQuotaLimitsType::class);

        $this->assertTrue($form->has('per_hour'));
        $this->assertTrue($form->has('per_day'));
    }

    public function testSubmitWithExistingData(): void
    {
        $existingData = [
            'per_hour' => 200,
            'per_day' => 2000,
        ];

        $form = $this->factory->create(SmtpQuotaLimitsType::class, $existingData);

        $this->assertEquals(200, $form->get('per_hour')->getData());
        $this->assertEquals(2000, $form->get('per_day')->getData());

        $formData = [
            'per_hour' => 300,
            'per_day' => 3000,
        ];

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $expected = [
            'per_hour' => 300,
            'per_day' => 3000,
        ];

        $this->assertEquals($expected, $form->getData());
    }
}
