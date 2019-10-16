<?php

namespace App\Block;

use Doctrine\Common\Persistence\ObjectManager;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractAdminBlockService;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatisticsBlockService extends AbstractAdminBlockService
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * Constructor.
     *
     * @param $name
     * @param EngineInterface $templating
     * @param ObjectManager   $manager
     */
    public function __construct($name, EngineInterface $templating, ObjectManager $manager)
    {
        parent::__construct($name, $templating);

        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $form, BlockInterface $block)
    {
        throw new \RuntimeException('Not used, this block renders an empty result if no block document can be found');
    }

    /**
     * {@inheritdoc}
     */
    public function buildCreateForm(FormMapper $form, BlockInterface $block)
    {
        throw new \RuntimeException('Not used, this block renders an empty result if no block document can be found');
    }

    /**
     * @param BlockContextInterface $blockContext
     * @param Response              $response
     *
     * @return Response
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $settings = $blockContext->getSettings();

        return $this->templating->renderResponse(
            $blockContext->getTemplate(),
            array(
                'block' => $blockContext->getBlock(),
                'settings' => $settings,
                'users_since' => (null !== $usersSince = $this->manager->getRepository('App:User')->findUsersSince(
                        new \DateTime('-7 days')
                    )) ? count($usersSince) : 0,
                'users_count' => $this->manager->getRepository('App:User')->count([]),
                'vouchers_count' => $vouchersCount = $this->manager->getRepository('App:Voucher')->count([]),
                'vouchers_redeemed' => $vouchersRedeemed = $this->manager->getRepository('App:Voucher')->countRedeemedVouchers(),
                'vouchers_ratio' => ($vouchersCount > 0) ? sprintf('%.2f%%', (float) (($vouchersRedeemed / $vouchersCount) * 100)) : '0%',
            ),
            $response
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'url' => false,
                'title' => 'Statistics',
                'template' => 'Block/block_statistics.html.twig',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeys(BlockInterface $block)
    {
        return array(
            'block_id' => $block->getId(),
            'updated_at' => $block->getUpdatedAt() ? $block->getUpdatedAt()->format('U') : strtotime('now'),
        );
    }
}
