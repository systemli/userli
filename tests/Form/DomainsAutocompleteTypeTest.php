<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\Domain;
use App\Form\DataTransformer\DomainsToIdsTransformer;
use App\Form\DomainsAutocompleteType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DomainsAutocompleteTypeTest extends TypeTestCase
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
        $type = new DomainsAutocompleteType(
            new DomainsToIdsTransformer($this->entityManager),
            $this->urlGenerator,
        );

        return [
            new PreloadedExtension([$type], []),
        ];
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(DomainsAutocompleteType::class);

        self::assertSame('domains_autocomplete', $form->getConfig()->getName());
    }

    public function testBlockPrefixes(): void
    {
        $form = $this->factory->create(DomainsAutocompleteType::class);
        $view = $form->createView();

        self::assertContains('form', $view->vars['block_prefixes']);
        self::assertContains('hidden', $view->vars['block_prefixes']);
        self::assertContains('domains_autocomplete', $view->vars['block_prefixes']);
    }

    public function testParentIsHiddenType(): void
    {
        $type = new DomainsAutocompleteType(
            new DomainsToIdsTransformer($this->entityManager),
            $this->urlGenerator,
        );

        self::assertSame(HiddenType::class, $type->getParent());
    }

    public function testViewVarsWithoutData(): void
    {
        $form = $this->factory->create(DomainsAutocompleteType::class);
        $view = $form->createView();

        self::assertSame('/settings/domains/search', $view->vars['autocomplete_url']);
        self::assertSame('name', $view->vars['autocomplete_label_field']);
        self::assertSame(0, $view->vars['autocomplete_min_chars']);
        self::assertSame([], $view->vars['autocomplete_selected']);
    }

    public function testViewVarsWithDomains(): void
    {
        $domain1 = new Domain();
        $domain1->setId(1);
        $domain1->setName('example.org');

        $domain2 = new Domain();
        $domain2->setId(2);
        $domain2->setName('example.com');

        $form = $this->factory->create(DomainsAutocompleteType::class);
        $form->setData([$domain1, $domain2]);
        $view = $form->createView();

        $expected = [
            ['id' => 1, 'name' => 'example.org'],
            ['id' => 2, 'name' => 'example.com'],
        ];
        self::assertSame($expected, $view->vars['autocomplete_selected']);
    }

    public function testSubmitValidIds(): void
    {
        $domain1 = new Domain();
        $domain1->setId(1);
        $domain1->setName('example.org');

        $domain2 = new Domain();
        $domain2->setId(2);
        $domain2->setName('example.com');

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findBy')->willReturn([$domain1, $domain2]);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $form = $this->factory->create(DomainsAutocompleteType::class);
        $form->submit('1,2');

        self::assertTrue($form->isSynchronized());
        self::assertCount(2, $form->getData());
        self::assertSame($domain1, $form->getData()[0]);
        self::assertSame($domain2, $form->getData()[1]);
    }

    public function testSubmitInvalidIds(): void
    {
        $domain1 = new Domain();
        $domain1->setId(1);
        $domain1->setName('example.org');

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findBy')->willReturn([$domain1]);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $form = $this->factory->create(DomainsAutocompleteType::class);
        $form->submit('1,999');

        self::assertFalse($form->isSynchronized());
    }

    public function testSubmitEmptyValue(): void
    {
        $form = $this->factory->create(DomainsAutocompleteType::class);
        $form->submit('');

        self::assertTrue($form->isSynchronized());
        self::assertSame([], $form->getData());
    }
}
