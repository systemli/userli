<?php

namespace App\Form;

use App\Form\DataTransformer\TextToEmailTransformer;
use App\Form\Model\BasicRegistration;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BasicRegistrationType extends AbstractType
{
    public const NAME = 'basic_registration';

    private string $domain;

    public function __construct(readonly Security $security)
    {
        $this->domain = $security->getUser()->getDomain()->getName();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transformer = new TextToEmailTransformer($this->domain);

        $builder
            ->add($builder->create(
                'email',
                TextType::class,
                ['label' => 'registration.label-email']
            )->addViewTransformer($transformer))
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'form.password'],
                'second_options' => ['label' => 'form.password_confirmation'],
                'invalid_message' => 'form.different-password',
            ])
            ->add('submit', SubmitType::class, ['label' => 'form.submit']);;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => BasicRegistration::class]);
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
