<?php

namespace App\Block;

use App\Counter\UserCounter;
use App\Counter\VoucherCounter;
use Doctrine\Common\Persistence\ObjectManager;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @author louis <louis@systemli.org>
 */
class StatisticsBlockService extends AbstractBlockService
{
    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var UserCounter
     */
    private $userCounter;
    /**
     * @var VoucherCounter
     */
    private $voucherCounter;

    /**
     * Constructor.
     *
     * @param $name
     * @param EngineInterface $templating
     * @param ObjectManager   $manager
     * @param UserCounter     $userCounter
     * @param VoucherCounter  $voucherCounter
     */
    public function __construct(
        $name = null,
        EngineInterface $templating = null,
        ObjectManager $manager,
        UserCounter $userCounter,
        VoucherCounter $voucherCounter
    ) {
        parent::__construct($name, $templating);

        $this->manager = $manager;
        $this->userCounter = $userCounter;
        $this->voucherCounter = $voucherCounter;
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
                'users_count' => $this->userCounter->getCount(),
                'vouchers_count' => $vouchersCount = $this->voucherCounter->getCount(),
                'vouchers_redeemed' => (null !== $vouchersRedeemed = $this->manager->getRepository('App:Voucher')->findAllRedeemedVouchers()) ? count($vouchersRedeemed) : 0,
                'vouchers_ratio' => ($vouchersCount > 0) ? sprintf(
                    '%.2f%%',
                    (float) ((count($vouchersRedeemed) / $vouchersCount) * 100)
                ) : '0%',
            ),
            $response
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {
        $this->configureSettings($resolver);
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
    public function load(BlockInterface $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getJavascripts($media)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getStylesheets($media)
    {
        return array();
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
