<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\OpenPgpKey;
use App\Form\OpenPgpKeyType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class OpenPgpKeyTypeTest extends TestCase
{
    private MockObject $translator;
    private MockObject $formBuilder;
    private OpenPgpKeyType $formType;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->method('trans')->willReturnCallback(static fn ($key) => $key);

        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
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

        $this->assertArrayHasKey('keyFile', $addedFields);
        $this->assertArrayHasKey('keyText', $addedFields);
        $this->assertArrayHasKey('submit', $addedFields);

        $this->assertSame(FileType::class, $addedFields['keyFile']);
        $this->assertSame(TextareaType::class, $addedFields['keyText']);
        $this->assertSame(SubmitType::class, $addedFields['submit']);
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $options = $resolver->resolve([]);
        $this->assertSame(OpenPgpKey::class, $options['data_class']);
    }

    public function testBlockPrefix(): void
    {
        $this->assertSame('upload_openpgp_key', $this->formType->getBlockPrefix());
    }

    public function testGetSubscribedEvents(): void
    {
        $events = OpenPgpKeyType::getSubscribedEvents();

        $this->assertArrayHasKey('form.submit', $events);
        $this->assertSame('ensureOneFieldIsSubmitted', $events['form.submit']);
    }
}
