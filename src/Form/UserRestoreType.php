<?php

declare(strict_types=1);

namespace App\Form;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<null>
 */
final class UserRestoreType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'required' => true,
            'first_options' => [
                'label' => 'form.password',
            ],
            'second_options' => [
                'label' => 'form.password_confirmation',
            ],
            'invalid_message' => 'different-password',
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(min: 12, minMessage: 'form.weak_password'),
                new Assert\NotCompromisedPassword(skipOnError: true),
            ],
        ]);

        $builder->add('submit', SubmitType::class);
    }
}
