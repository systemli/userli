<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Domain;
use App\Form\DataTransformer\DomainToIdTransformer;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * AJAX autocomplete form type for selecting a single Domain entity.
 *
 * Renders a hidden input that is enhanced by the ajax-autocomplete Stimulus
 * controller via the form theme block `domain_autocomplete_widget`.
 *
 * @extends AbstractType<Domain|null>
 */
final class DomainAutocompleteType extends AbstractType
{
    public function __construct(
        private readonly DomainToIdTransformer $transformer,
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
        $view->vars['autocomplete_url'] = $this->urlGenerator->generate('admin_domain_search');
        $view->vars['autocomplete_label_field'] = 'name';
        $view->vars['autocomplete_min_chars'] = 0;

        $domain = $form->getData();
        $view->vars['autocomplete_label'] = $domain instanceof Domain ? $domain->getName() : '';
    }

    #[Override]
    public function getParent(): string
    {
        return HiddenType::class;
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'domain_autocomplete';
    }
}
