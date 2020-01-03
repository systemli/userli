<?php

namespace App\Tests\Form;

use App\Entity\Domain;
use App\Form\Model\RecoveryProcess;
use App\Form\RecoveryProcessType;
use App\Repository\DomainRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class RecoveryProcessTypeTest extends TypeTestCase
{
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [new RecoveryProcessType($this->getManager())],
                []
                ),
        ];
    }

    public function testSubmitValidData()
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
        $manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $domain = new Domain();
        $domain->setName('example.com');

        $repository->method('getDefaultDomain')
            ->will($this->returnValue($domain));

        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
