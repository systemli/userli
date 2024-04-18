<?php

namespace App\Repository;

use App\Entity\Domain;
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
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends EntityRepository implements PasswordUpgraderInterface
{
    public function findById(int $id): ?User
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByDomainAndEmail(Domain $domain, string $email): ?User
    {
        return $this->findOneBy(['domain' => $domain, 'email' => $email]);
    }

    public function findUsersByString(Domain $domain, string $string, int $max, int $first): AbstractLazyCollection|LazyCriteriaCollection
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('domain', $domain));
        $criteria->andWhere(Criteria::expr()->contains('email', $string));
        $criteria->setMaxResults($max);
        $criteria->setFirstResult($first);
        return $this->matching($criteria);
    }

    /**
     * @return AbstractLazyCollection|(AbstractLazyCollection&Selectable)|LazyCriteriaCollection
     */
    public function findUsersSince(DateTime $dateTime)
    {
        return $this->matching(Criteria::create()->where(Criteria::expr()->gte('creationTime', $dateTime)));
    }

    /**
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

    public function findDeletedUsers(): array
    {
        return $this->findBy(['deleted' => true]);
    }

    public function countUsers(): int
    {
        return $this->matching(Criteria::create()
            ->where(Criteria::expr()->eq('deleted', false)))->count();
    }

    public function countDomainUsers(Domain $domain): int
    {
        return $this->matching(Criteria::create()
            ->where(Criteria::expr()->eq('domain', $domain))
            ->andWhere(Criteria::expr()->eq('deleted', false)))
            ->count();
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

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush($user);
    }
}
