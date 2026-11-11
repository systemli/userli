<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\Announcement;
use App\Entity\Domain;
use App\Entity\User;
use App\Enum\Roles;
use App\Mail\AnnouncementMailer;
use App\Message\SendAnnouncementMail;
use App\MessageHandler\SendAnnouncementMailHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class SendAnnouncementMailHandlerTest extends TestCase
{
    private Domain $domain;

    protected function setUp(): void
    {
        $this->domain = $this->createDomain(1, 'example.org');
    }

    public function testInvokeSendsMail(): void
    {
        $user = $this->createUser($this->domain);

        $mailer = $this->createMock(AnnouncementMailer::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with('user@example.org', 'Subject', 'Body');

        $this->createHandler($this->createAnnouncement(), $user, $mailer)(new SendAnnouncementMail(1, 1));
    }

    public function testInvokeSkipsDeletedUser(): void
    {
        $user = $this->createUser($this->domain);
        $user->setDeleted(true);

        $this->assertSkipped($this->createAnnouncement(), $user);
    }

    public function testInvokeSkipsSpamUser(): void
    {
        $user = $this->createUser($this->domain);
        $user->setRoles([Roles::USER, Roles::SPAM]);

        $this->assertSkipped($this->createAnnouncement(), $user);
    }

    public function testInvokeSkipsUserOfOtherDomain(): void
    {
        $user = $this->createUser($this->createDomain(2, 'example.com'));

        $this->assertSkipped($this->createAnnouncement(), $user);
    }

    public function testInvokeSkipsMissingEntities(): void
    {
        $this->assertSkipped(null, $this->createUser($this->domain));
        $this->assertSkipped($this->createAnnouncement(), null);
    }

    private function assertSkipped(?Announcement $announcement, ?User $user): void
    {
        $mailer = $this->createMock(AnnouncementMailer::class);
        $mailer->expects($this->never())->method('send');

        $this->createHandler($announcement, $user, $mailer)(new SendAnnouncementMail(1, 1));
    }

    private function createAnnouncement(): Announcement
    {
        return new Announcement($this->domain, 'Subject', 'Body', null, 1);
    }

    private function createUser(Domain $domain): User
    {
        $user = new User('user@example.org');
        $user->setRoles([Roles::USER]);
        $user->setDomain($domain);

        return $user;
    }

    private function createDomain(int $id, string $name): Domain
    {
        $domain = new Domain();
        $domain->setName($name);
        $domain->setId($id);

        return $domain;
    }

    private function createHandler(?Announcement $announcement, ?User $user, AnnouncementMailer $mailer): SendAnnouncementMailHandler
    {
        $announcementRepository = $this->createStub(EntityRepository::class);
        $announcementRepository->method('find')->willReturn($announcement);

        $userRepository = $this->createStub(EntityRepository::class);
        $userRepository->method('find')->willReturn($user);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [Announcement::class, $announcementRepository],
            [User::class, $userRepository],
        ]);

        return new SendAnnouncementMailHandler($em, $mailer);
    }
}
