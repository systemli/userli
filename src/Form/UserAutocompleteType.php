<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Form\DataTransformer\UserToIdTransformer;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * AJAX autocomplete form type for selecting a single User entity.
 *
 * Renders a hidden input that is enhanced by the ajax-autocomplete Stimulus
 * controller via the form theme block `user_autocomplete_widget`.
 *
 * @extends AbstractType<User|null>
 */
final class UserAutocompleteType extends AbstractType
{
    public function __construct(
        private readonly UserToIdTransformer $transformer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->transformer);
    }

    #[Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['autocomplete_url'] = $this->urlGenerator->generate('admin_user_search');
        $view->vars['autocomplete_label_field'] = 'email';
        $view->vars['autocomplete_min_chars'] = 2;

        $user = $form->getData();
        $view->vars['autocomplete_label'] = $user instanceof User ? $user->getEmail() : '';
    }

    #[Override]
    public function getParent(): string
    {
        return HiddenType::class;
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'user_autocomplete';
    }
}
