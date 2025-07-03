<?php

namespace App\Tests\EntityListener;

use App\Entity\User;
use App\Entity\Voucher;
use App\EntityListener\UserChangedListener;
use App\Enum\Roles;
use App\Handler\SuspiciousChildrenHandler;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class UserChangedListenerTest extends TestCase
{
    private Session $session;
    private Request $request;
    private RequestEvent $event;
    private SuspiciousChildrenHandler $suspiciousChildrenHandler;
    private VoucherRepository $voucherRepository;
    private UserChangedListener $listener;

    public function setUp(): void
    {
        $this->voucherRepository = $this->createMock(VoucherRepository::class);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')
            ->willReturn($this->voucherRepository);
        $this->suspiciousChildrenHandler = $this->createMock(SuspiciousChildrenHandler::class);
        $this->listener = new UserChangedListener($manager, $this->suspiciousChildrenHandler);

        $this->session = $this->createMock(Session::class);
        $this->request = $this->createMock(Request::class);
        $this->request->method('getSession')
            ->willReturn($this->session);
        $this->request->query = new InputBag();
        $this->event = $this->createMock(RequestEvent::class);
        $this->event->method('getRequest')
            ->willReturn($this->request);
    }

    public function testPreUpdateNoRoleChanges(): void
    {
        $user = new User();
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')
            ->willReturn(['someField' => [0, 1]]);

        $this->suspiciousChildrenHandler
            ->expects(self::never())
            ->method('sendReport');
        $this->listener->preUpdate($user, $args);
    }

    public function testPreUpdateOtherRoleChanges(): void
    {
        $user = new User();
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')
            ->willReturn(['roles' => [[Roles::USER], [Roles::USER, Roles::PERMANENT]]]);

        $this->suspiciousChildrenHandler
            ->expects(self::never())
            ->method('sendReport');
        $this->listener->preUpdate($user, $args);
    }

    public function testPreUpdateRoleSuspiciousRemoved(): void
    {
        $user = new User();
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')
            ->willReturn(['roles' => [[Roles::USER, Roles::SUSPICIOUS], [Roles::USER]]]);

        $this->suspiciousChildrenHandler
            ->expects(self::never())
            ->method('sendReport');
        $this->listener->preUpdate($user, $args);
    }

    public function testPreUpdateRoleSuspiciousAdded(): void
    {
        $user = new User();
        $user->setEmail('user@example.org');
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')
            ->willReturn(['roles' => [[Roles::USER], [Roles::USER, Roles::SUSPICIOUS]]]);

        $invitedUser1 = new User();
        $invitedUser1->setEmail('invited1@example.org');
        $voucher1 = new Voucher();
        $voucher1->setInvitedUser($invitedUser1);
        $invitedUser2 = new User();
        $invitedUser2->setEmail('invited2@example.org');
        $voucher2 = new Voucher();
        $voucher2->setInvitedUser($invitedUser2);
        $this->voucherRepository->method('getRedeemedVouchersByUser')
            ->willReturn([$voucher1, $voucher2]);

        $this->suspiciousChildrenHandler
            ->expects(self::once())
            ->method('sendReport')
            ->with([$invitedUser1->getEmail() => $user->getEmail(), $invitedUser2->getEmail() => $user->getEmail()]);
        $this->listener->preUpdate($user, $args);
    }
}
