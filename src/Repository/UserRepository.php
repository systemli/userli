<?php

namespace App\Repository;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Collection;
use DateTime;
use DateInterval;
use App\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\LazyCriteriaCollection;
use Exception;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends EntityRepository implements PasswordUpgraderInterface
{
    /**
     * @param $email
     *
     * @return User|null
     */
    public function findByEmail($email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * @param DateTime $dateTime
     * @return AbstractLazyCollection|(AbstractLazyCollection&Selectable)|LazyCriteriaCollection
     */
    public function findUsersSince(DateTime $dateTime)
    {
        return $this->matching(Criteria::create()->where(Criteria::expr()->gte('creationTime', $dateTime)));
    }

    /**
     * @param int $days
     * @return AbstractLazyCollection|(AbstractLazyCollection&Selectable)|LazyCriteriaCollection
     * @throws Exception
     */
    public function findInactiveUsers(int $days)
    {
        $expressionBuilder = Criteria::expr();

        if (0 === $days) {
            $expression = $expressionBuilder->eq('deleted', 0);
        } else {
            $dateTime = new DateTime();
            $dateTime->sub(new DateInterval('P'.$days.'D'));
            $expression = $expressionBuilder->andX(
                $expressionBuilder->eq('deleted', 0),
                $expressionBuilder->orX(
                    $expressionBuilder->lte('lastLoginTime', $dateTime),
                    $expressionBuilder->andX(
                        $expressionBuilder->eq('lastLoginTime', null),
                        $expressionBuilder->lte('updatedTime', $dateTime)
                    )
                )
            );
        }

        return $this->matching(new Criteria($expression));
    }

    /**
     * @return User[]|array
     */
    public function findDeletedUsers(): array
    {
        return $this->findBy(['deleted' => true]);
    }

    /**
     * @return int
     */
    public function countUsers(): int
    {
        return $this->matching(Criteria::create()
            ->where(Criteria::expr()->eq('deleted', false)))->count();
    }

    /**
     * @return int
     */
    public function countDeletedUsers(): int
    {
        return $this->matching(Criteria::create()
            ->where(Criteria::expr()->eq('deleted', true)))->count();
    }

    /**
     * @return int
     */
    public function countUsersWithRecoveryToken(): int
    {
        return $this->matching(Criteria::create()
            ->where(Criteria::expr()->eq('deleted', false))
            ->andWhere(Criteria::expr()->neq('recoverySecretBox', null))
        )->count();
    }

    /**
     * @return int
     */
    public function countUsersWithMailCrypt(): int
    {
        return $this->matching(Criteria::create()
            ->where(Criteria::expr()->eq('deleted', false))
            ->andWhere(Criteria::expr()->eq('mailCrypt', true))
        )->count();
    }

    /**
     * @return int
     */
    public function countUsersWithTwofactor(): int
    {
        return $this->matching(Criteria::create()
            ->where(Criteria::expr()->eq('deleted', false))
            ->andWhere(Criteria::expr()->eq('totpConfirmed', 1))
            ->andWhere(Criteria::expr()->neq('totpSecret', null))
        )->count();
    }
}
