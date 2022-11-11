<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Class UserRepository.
 */
class UserRepository extends AbstractRepository implements PasswordUpgraderInterface
{
    /**
     * @param $email
     *
     * @return object|User|null
     */
    public function findByEmail($email)
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection|User[]
     */
    public function findUsersSince(\DateTime $dateTime)
    {
        return $this->matching(Criteria::create()->where(Criteria::expr()->gte('creationTime', $dateTime)));
    }

    /**
     * @return \Doctrine\Common\Collections\Collection|User[]
     */
    public function findInactiveUsers(int $days)
    {
        $expressionBuilder = Criteria::expr();

        if (0 === $days) {
            $expression = $expressionBuilder->eq('deleted', 0);
        } else {
            $dateTime = new \DateTime();
            $dateTime->sub(new \DateInterval('P'.$days.'D'));
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

    public function countUsers(): int
    {
        return $this->matching(Criteria::create()
            ->where(Criteria::expr()->eq('deleted', false)))->count();
    }

    public function countDeletedUsers(): int
    {
        return $this->matching(Criteria::create()
            ->where(Criteria::expr()->eq('deleted', true)))->count();
    }

    public function countUsersWithRecoveryToken(): int
    {
        return $this->matching(Criteria::create()
            ->where(Criteria::expr()->eq('deleted', false))
            ->andWhere(Criteria::expr()->neq('recoverySecretBox', null))
        )->count();
    }

    public function countUsersWithMailCrypt(): int
    {
        return $this->matching(Criteria::create()
            ->where(Criteria::expr()->eq('deleted', false))
            ->andWhere(Criteria::expr()->eq('mailCrypt', true))
        )->count();
    }

    public function countUsersWithTwofactor(): int
    {
        return $this->matching(Criteria::create()
            ->where(Criteria::expr()->eq('deleted', false))
            ->andWhere(Criteria::expr()->eq('totpConfirmed', 1))
            ->andWhere(Criteria::expr()->neq('totpSecret', null))
        )->count();
    }

    public function upgradePassword(User $user, string $newHashedPassword): void
    {
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }
}
