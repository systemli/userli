<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\WebauthnCredentialCreate;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<WebauthnCredentialCreate>
 */
final class WebauthnCredentialCreateType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'account.webauthn.name', 'attr' => ['maxlength' => 64, 'placeholder' => 'account.webauthn.name-placeholder']])
            ->add('password', PasswordType::class, ['label' => 'form.actual-password', 'attr' => ['autocomplete' => 'current-password']])
            ->add('response', HiddenType::class, ['attr' => ['data-webauthn-target' => 'response']])
            ->add('submit', SubmitType::class, ['label' => 'account.webauthn.create', 'attr' => ['data-action' => 'webauthn#start']]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => WebauthnCredentialCreate::class]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'webauthn_credential';
    }
}
