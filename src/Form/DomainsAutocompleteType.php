<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Domain;
use App\Form\DataTransformer\DomainsToIdsTransformer;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * AJAX autocomplete form type for selecting multiple Domain entities.
 *
 * Renders a hidden input that is enhanced by the ajax-autocomplete Stimulus
 * controller in multi-select mode via the form theme block
 * `domains_autocomplete_widget`. Selected domains are shown as tag pills.
 *
 * @extends AbstractType<array<Domain>>
 */
final class DomainsAutocompleteType extends AbstractType
{
    public function __construct(
        private readonly DomainsToIdsTransformer $transformer,
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
        $view->vars['autocomplete_url'] = $this->urlGenerator->generate('settings_domain_search');
        $view->vars['autocomplete_label_field'] = 'name';
        $view->vars['autocomplete_min_chars'] = 0;

        $domains = $form->getData();
        $selected = [];
        if (is_array($domains)) {
            foreach ($domains as $domain) {
                if ($domain instanceof Domain) {
                    $selected[] = ['id' => $domain->getId(), 'name' => $domain->getName()];
                }
            }
        }

        $view->vars['autocomplete_selected'] = $selected;
    }

    #[Override]
    public function getParent(): string
    {
        return HiddenType::class;
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'domains_autocomplete';
    }
}
