<?php

namespace App\Tests\Form;

use App\Entity\Domain;
use App\Form\Model\RecoveryProcess;
use App\Form\RecoveryProcessType;
use App\Repository\DomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class RecoveryProcessTypeTest extends TypeTestCase
{
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
        $object->email = $email;
        $object->recoveryToken = $recoveryToken;

        // submit the data to the form directly
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($object, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    /**
     * Manager that returns default domain.
     */
    public function getManager()
    {
        $manager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $domain = new Domain();
        $domain->setName('example.com');

        $repository->method('getDefaultDomain')
            ->willReturn($domain);

        $manager->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
