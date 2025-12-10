<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\Password;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as BasePasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Password>
 */
class PasswordType extends AbstractType
{
    public const NAME = 'password';

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', BasePasswordType::class, ['label' => 'form.actual-password'])
            ->add('newPassword', RepeatedType::class, [
                'type' => BasePasswordType::class,
                'first_options' => ['label' => 'form.plain-password'],
                'second_options' => ['label' => 'form.plain-password_confirmation'],
                'invalid_message' => 'form.different-password',
            ])
            ->add('submit', SubmitType::class, ['label' => 'form.submit']);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Password::class]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
