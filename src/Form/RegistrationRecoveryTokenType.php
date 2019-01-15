<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author doobry <doobry@systemli.org>
 */
class RegistrationRecoveryTokenType extends AbstractType
{
    const NAME = 'registration_recovery_token';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ack', CheckboxType::class, [
                'required' => false,
                'label' => 'form.registration-recovery-token-ack',
            ])
            ->add('recoveryToken', HiddenType::class)
            ->add('submit', SubmitType::class, ['label' => 'form.registration-recovery-token-next-button']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => 'App\Form\Model\RegistrationRecoveryToken']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
