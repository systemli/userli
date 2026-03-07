<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\DataTransformer\DomainToIdTransformer;
use App\Form\DataTransformer\UserToIdTransformer;
use App\Form\DomainAutocompleteType;
use App\Form\UserAutocompleteType;
use App\Form\VoucherType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class VoucherTypeTest extends TypeTestCase
{
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->urlGenerator->method('generate')->willReturn('/settings/search');
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $userAutocomplete = new UserAutocompleteType(
            new UserToIdTransformer($this->entityManager),
            $this->urlGenerator,
        );
        $domainAutocomplete = new DomainAutocompleteType(
            new DomainToIdTransformer($this->entityManager),
            $this->urlGenerator,
        );

        return [
            new PreloadedExtension([$userAutocomplete, $domainAutocomplete], []),
        ];
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(VoucherType::class);
        $view = $form->createView();

        self::assertArrayHasKey('code', $view->children);
        self::assertArrayHasKey('user', $view->children);
        self::assertArrayHasKey('domain', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testUserFieldIsAutocomplete(): void
    {
        $form = $this->factory->create(VoucherType::class);
        $view = $form->createView();

        self::assertContains('user_autocomplete', $view->children['user']->vars['block_prefixes']);
    }

    public function testDomainFieldIsAutocomplete(): void
    {
        $form = $this->factory->create(VoucherType::class);
        $view = $form->createView();

        self::assertContains('domain_autocomplete', $view->children['domain']->vars['block_prefixes']);
    }

    public function testUserFieldHasAutocompleteVars(): void
    {
        $form = $this->factory->create(VoucherType::class);
        $view = $form->createView();

        self::assertArrayHasKey('autocomplete_url', $view->children['user']->vars);
        self::assertSame('email', $view->children['user']->vars['autocomplete_label_field']);
        self::assertSame(2, $view->children['user']->vars['autocomplete_min_chars']);
    }

    public function testDomainFieldHasAutocompleteVars(): void
    {
        $form = $this->factory->create(VoucherType::class);
        $view = $form->createView();

        self::assertArrayHasKey('autocomplete_url', $view->children['domain']->vars);
        self::assertSame('name', $view->children['domain']->vars['autocomplete_label_field']);
        self::assertSame(0, $view->children['domain']->vars['autocomplete_min_chars']);
    }
}
