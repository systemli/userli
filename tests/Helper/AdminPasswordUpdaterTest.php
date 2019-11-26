<?php

namespace App\Tests\Helper;

use App\Entity\User;
use App\Helper\AdminPasswordUpdater;
use App\Helper\PasswordUpdater;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use App\Security\Encoder\PasswordHashEncoder;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class AdminPasswordUpdaterTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $defaultDomain;

    public function setUp()
    {
        $this->defaultDomain = 'example.org';
    }

    public function testGetDefaultDomain()
    {
        $adminPasswordUpdater = new AdminPasswordUpdater(
            $this->getManager(null),
            $this->getUpdater(),
            $this->defaultDomain);

        self::assertEquals(
            $this->defaultDomain,
            $adminPasswordUpdater->getDefaultDomain()->getName());
    }

    public function testUpdateAdminPassword()
    {
        $admin = new User();
        $admin->setPlainPassword('password');
        $admin->setPassword('impossible_login');

        $adminPasswordUpdater = new AdminPasswordUpdater(
            $this->getManager($admin),
            $this->getUpdater(),
            $this->defaultDomain);

        $adminPasswordUpdater->updateAdminPassword('newpassword');

        $this->assertEquals('newpassword', $admin->getPlainPassword());
        $this->assertNotEquals('impossible_login', $admin->getPassword());
    }

    public function getManager($object)
    {
        $domainRepo = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $domainRepo->method('findByName')->willReturn(null);

        $userRepo = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepo->method('findByEmail')->willReturn($object);

        $manager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $manager->method('getRepository')->willReturnMap(
            [
                ['App:Domain', $domainRepo],
                ['App:User', $userRepo],
            ]);

        return $manager;
    }

    public function getUpdater()
    {
        $encoderFactory = $this->getMockBuilder(EncoderFactoryInterface::class)
            ->getMock();
        $encoderFactory->expects($this->any())->method('getEncoder')->willReturn(new PasswordHashEncoder());

        return new PasswordUpdater($encoderFactory);
    }
}
