<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\OpenPgpKey;
use Override;
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
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<OpenPgpKey>
 */
final class OpenPgpKeyType extends AbstractType implements EventSubscriberInterface
{
    public const NAME = 'upload_openpgp_key';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('keyFile', FileType::class, [
                'label' => 'openpgp-key-file',
                'help' => 'openpgp-key-file',
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => [
                            'application/pgp-keys',
                            'text/plain',
                        ],
                        'mimeTypesMessage' => $this->translator->trans('openpgp-key-file-mimetype'),
                    ]),
                ],
            ])
            ->add('keyText', TextareaType::class, [
                'label' => 'openpgp-key-text',
                'required' => false,
                'attr' => ['placeholder' => 'openpgp-key-text-placeholder'],
            ])
            ->add('submit', SubmitType::class, ['label' => 'openpgp-key-submit']);

        $builder->addEventSubscriber($this);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => OpenPgpKey::class]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::SUBMIT => 'ensureOneFieldIsSubmitted',
        ];
    }

    public function ensureOneFieldIsSubmitted(FormEvent $event): void
    {
        /** @var OpenPgpKey $submittedData */
        $submittedData = $event->getData();

        if ((null === $submittedData->getKeyFile() && null === $submittedData->getKeyText())
            || (null !== $submittedData->getKeyFile() && null !== $submittedData->getKeyText())) {
            throw new TransformationFailedException('exactly one of keyFile or keyText must be set', 0, null, $this->translator->trans('openpgp-key-select-one'));
        }
    }
}
