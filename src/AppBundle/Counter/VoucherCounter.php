<?php

namespace AppBundle\Counter;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class VoucherCounter.
 */
class VoucherCounter
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * UserCounter constructor.
     *
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        $qb = $this->manager->getRepository('AppBundle:Voucher')->createQueryBuilder('a');
        $query = $qb->select('COUNT(a.id)')->getQuery();

        try {
            $count = (int) $query
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            $count = 0;
        }

        return $count;
    }
}
