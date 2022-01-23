<?php

namespace App\Block;

use Doctrine\Common\Persistence\ObjectManager;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\BlockServiceInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatisticsBlockService implements BlockServiceInterface
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * StatisticsBlockService constructor.
     */
    public function __construct(EngineInterface $templating, ObjectManager $manager)
    {
        $this->templating = $templating;
        $this->manager = $manager;
    }

    public function execute(BlockContextInterface $blockContext, Response $response = null): Response
    {
        $settings = $blockContext->getSettings();

        return $this->templating->renderResponse(
            $blockContext->getTemplate(),
            [
                'block' => $blockContext->getBlock(),
                'settings' => $settings,
                'users_since' => (null !== $usersSince = $this->manager->getRepository('App:User')->findUsersSince(
                        new \DateTime('-7 days')
                    )) ? count($usersSince) : 0,
                'users_count' => $this->manager->getRepository('App:User')->count([]),
                'vouchers_count' => $vouchersCount = $this->manager->getRepository('App:Voucher')->count([]),
                'vouchers_redeemed' => $vouchersRedeemed = $this->manager->getRepository('App:Voucher')->countRedeemedVouchers(),
                'vouchers_ratio' => ($vouchersCount > 0) ? sprintf('%.2f%%', (float) (($vouchersRedeemed / $vouchersCount) * 100)) : '0%',
            ],
            $response
        );
    }

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

    public function getCacheKeys(BlockInterface $block): array
    {
        return [
            'block_id' => $block->getId(),
            'updated_at' => $block->getUpdatedAt() ? $block->getUpdatedAt()->format('U') : time(),
        ];
    }

    public function load(BlockInterface $block): void
    {
    }
}
