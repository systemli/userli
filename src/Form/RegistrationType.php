<?php

namespace App\Form;

use App\Form\DataTransformer\TextToEmailTransformer;
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
     *
     * @param string $domain
     */
    public function __construct(string $domain)
    {
        $this->domain = $domain;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new TextToEmailTransformer($this->domain);

        $builder
            ->add('voucher', TextType::class, array('label' => 'form.voucher'))
            ->add($builder->create(
                'email',
                TextType::class,
                array('label' => 'registration.label-email')
            )->addViewTransformer($transformer))
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'first_options' => array('label' => 'form.password'),
                'second_options' => array('label' => 'form.password_confirmation'),
                'invalid_message' => 'form.different-password',
            ))
            ->add('submit', SubmitType::class, array('label' => 'form.submit'));
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
