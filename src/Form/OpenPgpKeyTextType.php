<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Model\OpenPgpKeyText;

class OpenPgpKeyTextType extends AbstractType
{
    const NAME = 'upload_openpgp_key_text';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('key', TextareaType::class, [
                'attr' => ['placeholder' => 'form.openpgp-key-text']
            ])
            ->add('submit', SubmitType::class, ['label' => 'form.openpgp-key-text-submit']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => OpenPgpKeyText::class]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
