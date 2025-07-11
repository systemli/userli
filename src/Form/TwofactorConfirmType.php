<?php

namespace App\Form;

use App\Form\Model\TwofactorConfirm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TwofactorConfirmType extends AbstractType
{
    public const NAME = 'twofactor_confirm';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'required' => true,
                'label' => 'form.twofactor-login-auth-code',
            ])
            ->add('submit', SubmitType::class, ['label' => 'form.verify']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TwofactorConfirm::class]);
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
