<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\PasswordConfirmation;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<PasswordConfirmation>
 */
final class PasswordConfirmationType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, ['label' => $options['password_label']])
            ->add('submit', SubmitType::class, ['label' => $options['submit_label']]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PasswordConfirmation::class,
            'password_label' => 'form.delete-password',
            'submit_label' => 'form.submit',
        ]);
        $resolver->setAllowedTypes('password_label', 'string');
        $resolver->setAllowedTypes('submit_label', 'string');
    }
}
