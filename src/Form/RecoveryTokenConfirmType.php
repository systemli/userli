<?php

namespace App\Form;

use App\Form\Model\RecoveryTokenConfirm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecoveryTokenConfirmType extends AbstractType
{
    public const NAME = 'recovery_token_confirm';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('confirm', CheckboxType::class, [
                'required' => true,
                'label' => 'form.registration-recovery-token-confirm',
            ])
            ->add('recoveryToken', HiddenType::class)
            ->add('submit', SubmitType::class, ['label' => 'form.registration-recovery-token-next-button']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RecoveryTokenConfirm::class]);
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
