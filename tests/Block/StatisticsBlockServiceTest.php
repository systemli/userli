<?php

declare(strict_types=1);

namespace App\Tests\Block;

use App\Block\StatisticsBlockService;
use App\Entity\User;
use App\Entity\Voucher;
use App\Repository\UserRepository;
use App\Repository\VoucherRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

class StatisticsBlockServiceTest extends TestCase
{
    public function testExecuteRendersTemplateWithStatistics(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findUsersSince')->willReturn([new User('a@b.org'), new User('c@d.org')]);
        $userRepository->method('count')->willReturn(100);

        $voucherRepository = $this->createStub(VoucherRepository::class);
        $voucherRepository->method('count')->willReturn(50);
        $voucherRepository->method('countRedeemedVouchers')->willReturn(25);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnCallback(
            static function (string $class) use ($userRepository, $voucherRepository) {
                return match ($class) {
                    User::class => $userRepository,
                    Voucher::class => $voucherRepository,
                };
            }
        );

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with(
                'Block/block_statistics.html.twig',
                $this->callback(static function (array $context) {
                    return $context['users_since'] === 2
                        && $context['users_count'] === 100
                        && $context['vouchers_count'] === 50
                        && $context['vouchers_redeemed'] === 25
                        && $context['vouchers_ratio'] === '50.00%';
                })
            )
            ->willReturn('<div>rendered</div>');

        $block = $this->createStub(BlockInterface::class);

        $blockContext = $this->createStub(BlockContextInterface::class);
        $blockContext->method('getSettings')->willReturn(['url' => false, 'title' => 'Statistics']);
        $blockContext->method('getTemplate')->willReturn('Block/block_statistics.html.twig');
        $blockContext->method('getBlock')->willReturn($block);

        $response = new Response();

        $service = new StatisticsBlockService($twig, $manager);
        $result = $service->execute($blockContext, $response);

        self::assertSame('<div>rendered</div>', $result->getContent());
    }

    public function testExecuteWithZeroVouchersShowsZeroPercent(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findUsersSince')->willReturn([]);
        $userRepository->method('count')->willReturn(0);

        $voucherRepository = $this->createStub(VoucherRepository::class);
        $voucherRepository->method('count')->willReturn(0);
        $voucherRepository->method('countRedeemedVouchers')->willReturn(0);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnCallback(
            static function (string $class) use ($userRepository, $voucherRepository) {
                return match ($class) {
                    User::class => $userRepository,
                    Voucher::class => $voucherRepository,
                };
            }
        );

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(static function (array $context) {
                    return $context['users_since'] === 0
                        && $context['vouchers_ratio'] === '0%';
                })
            )
            ->willReturn('');

        $block = $this->createStub(BlockInterface::class);

        $blockContext = $this->createStub(BlockContextInterface::class);
        $blockContext->method('getSettings')->willReturn([]);
        $blockContext->method('getTemplate')->willReturn('Block/block_statistics.html.twig');
        $blockContext->method('getBlock')->willReturn($block);

        $response = new Response();
        $service = new StatisticsBlockService($twig, $manager);
        $service->execute($blockContext, $response);
    }

    public function testConfigureSettingsSetsDefaults(): void
    {
        $twig = $this->createStub(Environment::class);
        $manager = $this->createStub(EntityManagerInterface::class);

        $service = new StatisticsBlockService($twig, $manager);

        $resolver = new OptionsResolver();
        $service->configureSettings($resolver);

        $resolved = $resolver->resolve([]);
        self::assertFalse($resolved['url']);
        self::assertSame('Statistics', $resolved['title']);
        self::assertSame('Block/block_statistics.html.twig', $resolved['template']);
    }

    public function testGetCacheKeysReturnsBlockIdAndUpdatedAt(): void
    {
        $twig = $this->createStub(Environment::class);
        $manager = $this->createStub(EntityManagerInterface::class);

        $updatedAt = new DateTime('2026-01-15 10:00:00');

        $block = $this->createStub(BlockInterface::class);
        $block->method('getId')->willReturn(42);
        $block->method('getUpdatedAt')->willReturn($updatedAt);

        $service = new StatisticsBlockService($twig, $manager);
        $keys = $service->getCacheKeys($block);

        self::assertSame(42, $keys['block_id']);
        self::assertSame($updatedAt->format('U'), $keys['updated_at']);
    }

    public function testGetCacheKeysUsesTimeWhenUpdatedAtIsNull(): void
    {
        $twig = $this->createStub(Environment::class);
        $manager = $this->createStub(EntityManagerInterface::class);

        $block = $this->createStub(BlockInterface::class);
        $block->method('getId')->willReturn(1);
        $block->method('getUpdatedAt')->willReturn(null);

        $service = new StatisticsBlockService($twig, $manager);
        $keys = $service->getCacheKeys($block);

        self::assertSame(1, $keys['block_id']);
        self::assertIsInt($keys['updated_at']);
    }

    public function testLoadDoesNothing(): void
    {
        $twig = $this->createStub(Environment::class);
        $manager = $this->createStub(EntityManagerInterface::class);

        $block = $this->createStub(BlockInterface::class);

        $service = new StatisticsBlockService($twig, $manager);
        // load() is a no-op, just verify it doesn't throw
        $service->load($block);
        $this->addToAssertionCount(1);
    }
}
