<?php

namespace App\Form;

use App\Form\DataTransformer\TextToEmailTransformer;
use Doctrine\Common\Persistence\ObjectManager;
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
    const NAME = 'registration';

    /**
     * @var string
     */
    private $domain;

    /**
     * RegistrationType constructor.
     */
    public function __construct(ObjectManager $manager)
    {
        $this->domain = $manager->getRepository('App:Domain')->getDefaultDomain()->getName();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new TextToEmailTransformer($this->domain);

        $builder
            ->add('voucher', TextType::class, [
                'label' => 'form.voucher',
                'attr' => [
                    'autocomplete' => 'off',
                    'readonly' => (null === $options['data']->getVoucher()) ? false : true,
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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => 'App\Form\Model\Registration']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
