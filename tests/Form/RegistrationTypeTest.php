<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\Registration;
use App\Form\RegistrationType;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationTypeTest extends TestCase
{
    private Stub $formBuilder;
    private RegistrationType $formType;

    protected function setUp(): void
    {
        $this->formBuilder = $this->createStub(FormBuilderInterface::class);
        $this->formType = new RegistrationType();
    }

    public function testBuildFormAddsAllFields(): void
    {
        $registration = new Registration();
        $registration->setVoucher('');

        $childBuilder = $this->createStub(FormBuilderInterface::class);
        $childBuilder->method('addViewTransformer')->willReturnSelf();

        $this->formBuilder->method('create')
            ->with('email', TextType::class, $this->anything())
            ->willReturn($childBuilder);

        $addedFields = [];
        $this->formBuilder->method('add')
            ->willReturnCallback(function ($name) use (&$addedFields) {
                if (is_string($name)) {
                    $addedFields[] = $name;
                } else {
                    $addedFields[] = 'email'; // FormBuilderInterface was passed
                }

                return $this->formBuilder;
            });

        $this->formType->buildForm($this->formBuilder, [
            'data' => $registration,
            'domain' => 'example.org',
        ]);

        self::assertContains('voucher', $addedFields);
        self::assertContains('password', $addedFields);
        self::assertContains('submit', $addedFields);
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $options = $resolver->resolve(['domain' => 'example.org']);
        self::assertSame(Registration::class, $options['data_class']);
        self::assertSame('example.org', $options['domain']);
    }

    public function testBlockPrefix(): void
    {
        self::assertSame('registration', $this->formType->getBlockPrefix());
    }
}
