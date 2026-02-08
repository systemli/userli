<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\Domain;
use App\Form\Model\RecoveryProcess;
use App\Form\RecoveryProcessType;
use App\Repository\DomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class RecoveryProcessTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [new RecoveryProcessType($this->getManager())],
                []
            ),
        ];
    }

    public function testSubmitValidData(): void
    {
        $email = 'user@example.org';
        $recoveryToken = 'recovery-token';
        $formData = [
            'email' => $email,
            'recoveryToken' => $recoveryToken,
        ];

        $form = $this->factory->create(RecoveryProcessType::class);

        $object = new RecoveryProcess();
        $object->setEmail($email);
        $object->setRecoveryToken($recoveryToken);

        // submit the data to the form directly
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($object, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        // Assert that the form field has the correct attributes
        self::assertSame('off', $children['recoveryToken']->vars['attr']['autocomplete']);

        foreach (array_keys($formData) as $key) {
            self::assertArrayHasKey($key, $children);
        }
    }

    /**
     * Manager that returns default domain.
     */
    public function getManager()
    {
        $manager = $this->createStub(EntityManagerInterface::class);

        $repository = $this->createStub(DomainRepository::class);

        $domain = new Domain();
        $domain->setName('example.com');

        $repository->method('getDefaultDomain')
            ->willReturn($domain);

        $manager->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
