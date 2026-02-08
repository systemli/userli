<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\OpenPgpKey;
use App\Form\OpenPgpKeyType;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class OpenPgpKeyTypeTest extends TestCase
{
    private Stub $translator;
    private Stub $formBuilder;
    private OpenPgpKeyType $formType;

    protected function setUp(): void
    {
        $this->translator = $this->createStub(TranslatorInterface::class);
        $this->translator->method('trans')->willReturnCallback(static fn ($key) => $key);

        $this->formBuilder = $this->createStub(FormBuilderInterface::class);
        $this->formType = new OpenPgpKeyType($this->translator);
    }

    public function testBuildFormAddsAllFields(): void
    {
        $addedFields = [];

        $this->formBuilder->method('add')
            ->willReturnCallback(function ($name, $type) use (&$addedFields) {
                $addedFields[$name] = $type;

                return $this->formBuilder;
            });

        $this->formBuilder->method('addEventSubscriber')
            ->with($this->formType)
            ->willReturnSelf();

        $this->formType->buildForm($this->formBuilder, []);

        self::assertArrayHasKey('keyFile', $addedFields);
        self::assertArrayHasKey('keyText', $addedFields);
        self::assertArrayHasKey('submit', $addedFields);

        self::assertSame(FileType::class, $addedFields['keyFile']);
        self::assertSame(TextareaType::class, $addedFields['keyText']);
        self::assertSame(SubmitType::class, $addedFields['submit']);
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $options = $resolver->resolve([]);
        self::assertSame(OpenPgpKey::class, $options['data_class']);
    }

    public function testBlockPrefix(): void
    {
        self::assertSame('upload_openpgp_key', $this->formType->getBlockPrefix());
    }

    public function testGetSubscribedEvents(): void
    {
        $events = OpenPgpKeyType::getSubscribedEvents();

        self::assertArrayHasKey('form.submit', $events);
        self::assertSame('ensureOneFieldIsSubmitted', $events['form.submit']);
    }
}
