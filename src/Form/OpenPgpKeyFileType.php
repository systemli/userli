<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Model\OpenPgpKeyFile;

class OpenPgpKeyFileType extends AbstractType
{
    const NAME = 'upload_openpgp_key_file';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('key', FileType::class, [
                'label' => 'form.openpgp-key-file',
                'help' => 'form.openpgp-key-file'])
            ->add('submit', SubmitType::class, ['label' => 'form.openpgp-key-file-submit']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => OpenPgpKeyFile::class]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
