<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Domain;
use App\Form\DataTransformer\TextToEmailTransformer;
use App\Form\Model\Registration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RegistrationType.
 */
class RegistrationType extends AbstractType
{
    public const NAME = 'registration';

    private string $domain;

    /**
     * RegistrationType constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->domain = $manager->getRepository(Domain::class)->getDefaultDomain()->getName();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transformer = new TextToEmailTransformer($this->domain);

        $builder
            ->add('voucher', TextType::class, [
                'label' => 'form.voucher',
                'attr' => [
                    'autocomplete' => 'off',
                    'readonly' => ('' !== $options['data']->getVoucher()),
                ],
            ])
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
            ->add('submit', SubmitType::class, ['label' => 'form.submit']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Registration::class]);
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
