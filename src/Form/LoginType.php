<?php

declare(strict_types=1);

namespace App\Form;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class LoginType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('_username', EmailType::class, [
                'label' => 'form.email',
                'required' => true,
                'attr' => [
                    'autocomplete' => 'username email',
                    'autofocus' => true,
                    'placeholder' => '',
                ],
                'help' => 'form.email-help',
                'data' => $options['last_username'],
            ])
            ->add('_password', PasswordType::class, [
                'label' => 'form.password',
                'required' => true,
                'attr' => [
                    'autocomplete' => 'current-password',
                ],
                'help' => 'form.password-help',
            ])
            ->add('_remember_me', CheckboxType::class, [
                'label' => 'form.remember-me',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.signin',
                'attr' => [
                    'class' => 'w-full',
                ],
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'last_username' => '',
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        // Return empty string to use field names without prefix (e.g., _username instead of login[_username])
        return '';
    }
}
