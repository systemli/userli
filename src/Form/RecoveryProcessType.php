<?php

namespace App\Form;

use App\Form\DataTransformer\OptionalDomainEmailTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecoveryProcessType extends AbstractType
{
    const NAME = 'recovery_process';

    /**
     * @var string
     */
    private $domain;

    /**
     * RecoveryProcessType constructor.
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
        $transformer = new OptionalDomainEmailTransformer($this->domain);

        $builder
            ->add($builder->create(
                'email',
                TextType::class, array('label' => 'form.email')
            )->addViewTransformer($transformer))
            ->add('recoveryToken', PasswordType::class, array('label' => 'form.recovery-token'))
            ->add('submit', SubmitType::class, array('label' => 'form.recovery-start'));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => 'App\Form\Model\RecoveryProcess']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
