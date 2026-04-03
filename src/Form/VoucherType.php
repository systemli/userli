<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\VoucherModel;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<VoucherModel>
 */
final class VoucherType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class)
            ->add('user', UserAutocompleteType::class);

        if ($options['is_admin']) {
            $builder->add('domain', DomainAutocompleteType::class);
        }

        $builder->add('submit', SubmitType::class);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VoucherModel::class,
            'is_admin' => false,
        ]);
        $resolver->setAllowedTypes('is_admin', 'bool');
    }
}
