<?php

namespace App\Form;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Model\OpenPgpKey;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class OpenPgpKeyType extends AbstractType implements EventSubscriberInterface
{
    public const NAME = 'upload_openpgp_key_file';

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('keyFile', FileType::class, [
                'label' => 'form.openpgp-key-file',
                'help' => 'form.openpgp-key-file',
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => [
                            'application/pgp-keys',
                            'text/plain',
                        ],
                        'mimeTypesMessage' => $this->translator->trans('form.openpgp-key-file-mimetype'),
                    ])
                ],
            ])
            ->add('keyText', TextareaType::class, [
                'label' => 'form.openpgp-key-text',
                'required' => false,
                'attr' => ['placeholder' => 'form.openpgp-key-text-placeholder'],
            ])
            ->add('submit', SubmitType::class, ['label' => 'form.openpgp-key-submit']);

        $builder->addEventSubscriber($this);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => OpenPgpKey::class]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::SUBMIT => 'ensureOneFieldIsSubmitted',
        ];
    }

    public function ensureOneFieldIsSubmitted(FormEvent $event): void
    {
        $submittedData = $event->getData();

        if ((null === $submittedData->getKeyFile() && null === $submittedData->getKeyText()) ||
            (null !== $submittedData->getKeyFile() && null !== $submittedData->getKeyText())) {
            throw new TransformationFailedException(
                'exactly one of keyFile or keyText must be set',
                0, // code
                null, // previous
                $this->translator->trans('form.openpgp-key-select-one') // user message
            );
        }
    }
}
