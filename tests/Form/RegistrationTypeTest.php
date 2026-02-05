<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\Domain;
use App\Form\Model\Registration;
use App\Form\RegistrationType;
use App\Repository\DomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationTypeTest extends TestCase
{
    private MockObject $entityManager;
    private MockObject $formBuilder;
    private RegistrationType $formType;

    protected function setUp(): void
    {
        $domain = $this->createMock(Domain::class);
        $domain->method('getName')->willReturn('example.org');

        $domainRepository = $this->createMock(DomainRepository::class);
        $domainRepository->method('getDefaultDomain')->willReturn($domain);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('getRepository')
            ->with(Domain::class)
            ->willReturn($domainRepository);

        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->formType = new RegistrationType($this->entityManager);
    }

    public function testBuildFormAddsAllFields(): void
    {
        $registration = new Registration();
        $registration->setVoucher('');

        $childBuilder = $this->createMock(FormBuilderInterface::class);
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

        $this->formType->buildForm($this->formBuilder, ['data' => $registration]);

        $this->assertContains('voucher', $addedFields);
        $this->assertContains('password', $addedFields);
        $this->assertContains('submit', $addedFields);
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $options = $resolver->resolve([]);
        $this->assertSame(Registration::class, $options['data_class']);
    }

    public function testBlockPrefix(): void
    {
        $this->assertSame('registration', $this->formType->getBlockPrefix());
    }
}
