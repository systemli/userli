<?php

namespace App\Form;

use App\Form\Model\RecoveryProcess;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecoveryProcessType extends AbstractType
{
    public const NAME = 'recovery_process';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class, ['label' => 'form.email'])
            ->add('recoveryToken', TextType::class, ['label' => 'form.recovery-token', 'attr' => ['autocomplete' => 'off']])
            ->add('submit', SubmitType::class, ['label' => 'form.recovery-start']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RecoveryProcess::class]);
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
