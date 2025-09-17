<?php

namespace App\Form;

use App\Form\Model\AliasCreate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RandomAliasCreateType extends AbstractType
{
    public const NAME = 'create_alias';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', TextType::class, [
                'required' => false,
                'label' => false, // hide visual label
                'attr' => [
                    'placeholder' => 'form.alias-note-placeholder',
                    'aria-label' => 'form.alias-note-placeholder',
                    'maxlength' => 40
                ]
            ])
            ->add('submit', SubmitType::class, ['label' => 'form.create-random-alias']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AliasCreate::class]);
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
