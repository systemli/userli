<?php

declare(strict_types=1);

namespace App\Form;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<mixed>
 */
final class ReservedNameImportType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\File(mimeTypes: ['text/plain'], maxSize: '1M'),
                ],
            ])
            ->add('submit', SubmitType::class);
    }
}
