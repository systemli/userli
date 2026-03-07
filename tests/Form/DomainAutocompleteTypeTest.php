<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\Domain;
use App\Form\DataTransformer\DomainToIdTransformer;
use App\Form\DomainAutocompleteType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DomainAutocompleteTypeTest extends TypeTestCase
{
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->urlGenerator->method('generate')->willReturn('/settings/domains/search');
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $type = new DomainAutocompleteType(
            new DomainToIdTransformer($this->entityManager),
            $this->urlGenerator,
        );

        return [
            new PreloadedExtension([$type], []),
        ];
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(DomainAutocompleteType::class);

        self::assertSame('domain_autocomplete', $form->getConfig()->getName());
    }

    public function testBlockPrefixes(): void
    {
        $form = $this->factory->create(DomainAutocompleteType::class);
        $view = $form->createView();

        self::assertContains('form', $view->vars['block_prefixes']);
        self::assertContains('hidden', $view->vars['block_prefixes']);
        self::assertContains('domain_autocomplete', $view->vars['block_prefixes']);
    }

    public function testParentIsHiddenType(): void
    {
        $type = new DomainAutocompleteType(
            new DomainToIdTransformer($this->entityManager),
            $this->urlGenerator,
        );

        self::assertSame(HiddenType::class, $type->getParent());
    }

    public function testViewVarsWithoutData(): void
    {
        $form = $this->factory->create(DomainAutocompleteType::class);
        $view = $form->createView();

        self::assertSame('/settings/domains/search', $view->vars['autocomplete_url']);
        self::assertSame('name', $view->vars['autocomplete_label_field']);
        self::assertSame(0, $view->vars['autocomplete_min_chars']);
        self::assertSame('', $view->vars['autocomplete_label']);
    }

    public function testViewVarsWithDomain(): void
    {
        $domain = new Domain();
        $domain->setId(7);
        $domain->setName('example.org');

        $form = $this->factory->create(DomainAutocompleteType::class);
        $form->setData($domain);
        $view = $form->createView();

        self::assertSame('example.org', $view->vars['autocomplete_label']);
    }

    public function testSubmitValidId(): void
    {
        $domain = new Domain();
        $domain->setId(7);
        $domain->setName('example.org');

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn($domain);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $form = $this->factory->create(DomainAutocompleteType::class);
        $form->submit('7');

        self::assertTrue($form->isSynchronized());
        self::assertSame($domain, $form->getData());
    }

    public function testSubmitInvalidId(): void
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $form = $this->factory->create(DomainAutocompleteType::class);
        $form->submit('999');

        self::assertFalse($form->isSynchronized());
    }

    public function testSubmitEmptyValue(): void
    {
        $form = $this->factory->create(DomainAutocompleteType::class);
        $form->submit('');

        self::assertTrue($form->isSynchronized());
        self::assertNull($form->getData());
    }
}
