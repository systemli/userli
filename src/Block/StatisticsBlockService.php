<?php

declare(strict_types=1);

namespace App\Block;

use App\Entity\User;
use App\Entity\Voucher;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\BlockServiceInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

final readonly class StatisticsBlockService implements BlockServiceInterface
{
    /**
     * StatisticsBlockService constructor.
     */
    public function __construct(private Environment $twig, private EntityManagerInterface $manager)
    {
    }

    #[Override]
    public function execute(BlockContextInterface $blockContext, ?Response $response = null): Response
    {
        $settings = $blockContext->getSettings();

        $rendered = $this->twig->render(
            $blockContext->getTemplate(),
            [
                'block' => $blockContext->getBlock(),
                'settings' => $settings,
                'users_since' => (null !== $usersSince = $this->manager->getRepository(User::class)->findUsersSince(
                    new DateTimeImmutable('-7 days')
                )) ? count($usersSince) : 0,
                'users_count' => $this->manager->getRepository(User::class)->count([]),
                'vouchers_count' => $vouchersCount = $this->manager->getRepository(Voucher::class)->count([]),
                'vouchers_redeemed' => $vouchersRedeemed = $this->manager->getRepository(Voucher::class)->countRedeemedVouchers(),
                'vouchers_ratio' => ($vouchersCount > 0) ? sprintf('%.2f%%', (float) (($vouchersRedeemed / $vouchersCount) * 100)) : '0%',
            ]
        );

        return $response->setContent($rendered);
    }

    #[Override]
    public function configureSettings(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'url' => false,
                'title' => 'Statistics',
                'template' => 'Block/block_statistics.html.twig',
            ]
        );
    }

    #[Override]
    public function getCacheKeys(BlockInterface $block): array
    {
        return [
            'block_id' => $block->getId(),
            'updated_at' => $block->getUpdatedAt() ? $block->getUpdatedAt()->format('U') : time(),
        ];
    }

    #[Override]
    public function load(BlockInterface $block): void
    {
    }
}
