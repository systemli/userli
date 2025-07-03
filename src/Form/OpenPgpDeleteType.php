<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OpenPgpDeleteType extends AbstractType
{
    public const NAME = 'delete_openpgp';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, ['label' => 'form.delete-password'])
            ->add('submit', SubmitType::class, ['label' => 'openpgp-delete']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => 'App\Form\Model\Delete']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
