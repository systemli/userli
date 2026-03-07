<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\DataTransformer\UserToIdTransformer;
use App\Form\UserAutocompleteType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserAutocompleteTypeTest extends TypeTestCase
{
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->urlGenerator->method('generate')->willReturn('/settings/users/search');
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $type = new UserAutocompleteType(
            new UserToIdTransformer($this->entityManager),
            $this->urlGenerator,
        );

        return [
            new PreloadedExtension([$type], []),
        ];
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(UserAutocompleteType::class);

        self::assertSame('user_autocomplete', $form->getConfig()->getName());
    }

    public function testBlockPrefixes(): void
    {
        $form = $this->factory->create(UserAutocompleteType::class);
        $view = $form->createView();

        self::assertContains('form', $view->vars['block_prefixes']);
        self::assertContains('hidden', $view->vars['block_prefixes']);
        self::assertContains('user_autocomplete', $view->vars['block_prefixes']);
    }

    public function testParentIsHiddenType(): void
    {
        $type = new UserAutocompleteType(
            new UserToIdTransformer($this->entityManager),
            $this->urlGenerator,
        );

        self::assertSame(HiddenType::class, $type->getParent());
    }

    public function testViewVarsWithoutData(): void
    {
        $form = $this->factory->create(UserAutocompleteType::class);
        $view = $form->createView();

        self::assertSame('/settings/users/search', $view->vars['autocomplete_url']);
        self::assertSame('email', $view->vars['autocomplete_label_field']);
        self::assertSame(2, $view->vars['autocomplete_min_chars']);
        self::assertSame('', $view->vars['autocomplete_label']);
    }

    public function testViewVarsWithUser(): void
    {
        $user = new User('admin@example.org');
        $user->setId(42);

        $form = $this->factory->create(UserAutocompleteType::class);
        $form->setData($user);
        $view = $form->createView();

        self::assertSame('admin@example.org', $view->vars['autocomplete_label']);
    }

    public function testSubmitValidId(): void
    {
        $user = new User('admin@example.org');
        $user->setId(42);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn($user);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $form = $this->factory->create(UserAutocompleteType::class);
        $form->submit('42');

        self::assertTrue($form->isSynchronized());
        self::assertSame($user, $form->getData());
    }

    public function testSubmitInvalidId(): void
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturn($repository);

        $form = $this->factory->create(UserAutocompleteType::class);
        $form->submit('999');

        self::assertFalse($form->isSynchronized());
    }

    public function testSubmitEmptyValue(): void
    {
        $form = $this->factory->create(UserAutocompleteType::class);
        $form->submit('');

        self::assertTrue($form->isSynchronized());
        self::assertNull($form->getData());
    }
}
