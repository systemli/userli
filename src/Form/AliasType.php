<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Model\Alias;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AliasType extends AbstractType
{
    public const NAME = 'alias';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alias', TextType::class, ['label' => 'form.new-alias'])
            ->add('user', EntityType::class, ['class' => User::class, 'choice_label' => 'email'])
            ->add('submit', SubmitType::class, ['label' => 'form.create-alias']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Alias::class]);
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
