<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\AliasCreate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<AliasCreate>
 */
class RandomAliasCreateType extends AbstractType
{
    public const NAME = 'create_alias';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('submit', SubmitType::class, ['label' => 'form.create-random-alias']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AliasCreate::class]);
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
