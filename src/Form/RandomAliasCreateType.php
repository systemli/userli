<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\AliasCreate;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<AliasCreate>
 */
final class RandomAliasCreateType extends AbstractType
{
    public const string NAME = 'create_alias';

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', TextType::class, [
                'required' => false,
                'label' => false, // hide visual label
                'attr' => [
                    'placeholder' => 'form.alias-note-placeholder',
                    'aria-label' => 'form.alias-note-placeholder',
                    'maxlength' => 40,
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'form.create-random-alias']);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AliasCreate::class]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
