<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\AliasCreate;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<AliasCreate>
 */
final class CustomAliasCreateType extends AbstractType
{
    public const NAME = 'create_custom_alias';

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alias', TextType::class, ['label' => 'form.new-custom-alias'])
            ->add('note', TextareaType::class, [
                'label' => 'form.alias-note',
                'required' => false,
                'attr' => [
                    'rows' => 1,
                    'placeholder' => 'form.alias-note-placeholder'
                ]
            ])
            ->add('submit', SubmitType::class, ['label' => 'form.create-custom-alias']);
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
