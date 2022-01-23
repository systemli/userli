<?php

namespace App\Form;

use App\Form\DataTransformer\OptionalDomainEmailTransformer;
use App\Form\Model\RecoveryProcess;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecoveryProcessType extends AbstractType
{
    public const NAME = 'recovery_process';

    /**
     * @var string
     */
    private $domain;

    /**
     * RecoveryProcessType constructor.
     */
    public function __construct(ObjectManager $manager)
    {
        $this->domain = $manager->getRepository('App:Domain')->getDefaultDomain()->getName();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transformer = new OptionalDomainEmailTransformer($this->domain);

        $builder
            ->add($builder->create(
                'email',
                TextType::class, ['label' => 'form.email']
            )->addViewTransformer($transformer))
            ->add('recoveryToken', PasswordType::class, ['label' => 'form.recovery-token'])
            ->add('submit', SubmitType::class, ['label' => 'form.recovery-start']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RecoveryProcess::class]);
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
