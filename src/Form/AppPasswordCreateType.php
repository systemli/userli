<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\AppPasswordCreate;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<AppPasswordCreate>
 */
final class AppPasswordCreateType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'account.app-passwords.name', 'attr' => ['maxlength' => 64, 'placeholder' => 'account.app-passwords.name-placeholder']])
            ->add('password', PasswordType::class, ['label' => 'form.password'])
            ->add('submit', SubmitType::class, ['label' => 'account.app-passwords.create']);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AppPasswordCreate::class]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'app_password_create';
    }
}
