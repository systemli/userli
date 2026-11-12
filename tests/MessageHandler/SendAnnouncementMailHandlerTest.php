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
use App\Repository\AnnouncementRepository;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

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

        $repository = $this->announcementRepository($this->createAnnouncement());
        $repository->expects($this->once())->method('incrementSentCount')->with(1);
        $repository->expects($this->never())->method('incrementNotSentCount');

        $this->createHandler($repository, $user, $mailer)(new SendAnnouncementMail(1, 1));
    }

    public function testInvokeDoesNotRetryWhenCountingFailsAfterSending(): void
    {
        $mailer = $this->createMock(AnnouncementMailer::class);
        $mailer->expects($this->once())->method('send');

        $repository = $this->announcementRepository($this->createAnnouncement());
        $repository->method('incrementSentCount')->willThrowException(new RuntimeException('DB gone'));

        $this->createHandler($repository, $this->createUser($this->domain), $mailer)(new SendAnnouncementMail(1, 1));
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

    public function testInvokeSkipsRemovedUser(): void
    {
        $this->assertSkipped($this->createAnnouncement(), null);
    }

    public function testInvokeIgnoresMissingAnnouncement(): void
    {
        $mailer = $this->createMock(AnnouncementMailer::class);
        $mailer->expects($this->never())->method('send');

        $repository = $this->announcementRepository(null);
        $repository->expects($this->never())->method('incrementSentCount');
        $repository->expects($this->never())->method('incrementNotSentCount');

        $this->createHandler($repository, $this->createUser($this->domain), $mailer)(new SendAnnouncementMail(1, 1));
    }

    private function assertSkipped(Announcement $announcement, ?User $user): void
    {
        $mailer = $this->createMock(AnnouncementMailer::class);
        $mailer->expects($this->never())->method('send');

        $repository = $this->announcementRepository($announcement);
        $repository->expects($this->once())->method('incrementNotSentCount')->with(1);
        $repository->expects($this->never())->method('incrementSentCount');

        $this->createHandler($repository, $user, $mailer)(new SendAnnouncementMail(1, 1));
    }

    /**
     * @return AnnouncementRepository&\PHPUnit\Framework\MockObject\MockObject
     */
    private function announcementRepository(?Announcement $announcement): AnnouncementRepository
    {
        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->method('find')->willReturn($announcement);

        return $repository;
    }

    private function createAnnouncement(): Announcement
    {
        return new Announcement($this->domain, 'Subject', 'Body', null, 1, 'token');
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

    private function createHandler(AnnouncementRepository $announcementRepository, ?User $user, AnnouncementMailer $mailer): SendAnnouncementMailHandler
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('find')->willReturn($user);

        return new SendAnnouncementMailHandler($announcementRepository, $userRepository, $mailer, $this->createStub(LoggerInterface::class));
    }
}
