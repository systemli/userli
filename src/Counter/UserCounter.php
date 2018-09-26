<?php

namespace App\Counter;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class UserCounter.
 */
class UserCounter
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
        $qb = $this->manager->getRepository('App:User')->createQueryBuilder('a');
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
