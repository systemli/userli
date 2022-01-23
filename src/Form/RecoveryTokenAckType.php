<?php

namespace App\Form;

use App\Form\Model\RecoveryTokenAck;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecoveryTokenAckType extends AbstractType
{
    public const NAME = 'recovery_token_ack';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ack', CheckboxType::class, [
                'required' => true,
                'label' => 'form.registration-recovery-token-ack',
            ])
            ->add('recoveryToken', HiddenType::class)
            ->add('submit', SubmitType::class, ['label' => 'form.registration-recovery-token-next-button']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RecoveryTokenAck::class]);
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
