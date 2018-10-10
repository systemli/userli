<?php

namespace App\Creator;

use App\Entity\User;
use App\Entity\Alias;
use App\Exception\ValidationException;
use App\Factory\AliasFactory;
use App\Helper\RandomStringGenerator;

/**
 * Class AliasCreator.
 */
class AliasCreator extends AbstractCreator
{
    /**
     * @param User $user
     * @param null|string $localPart
     * @return Alias
     * @throws \App\Exception\ValidationException
     */
    public function create(User $user, ?string $localPart): Alias
    {
        $alias = AliasFactory::create($user, $localPart);

        if (null === $localPart) {
            $alias = $this->validateAndUpdate($alias);
        }

        $this->validate($alias, ['Default', 'unique']);
        $this->save($alias);

        return $alias;
    }

    /**
     * @param Alias $alias
     * @return Alias
     */
    private function validateAndUpdate(Alias $alias)
    {
        try {
            $this->validate($alias, ['unique']);
        } catch (ValidationException $e) {
            $localPart = RandomStringGenerator::generate(24, false);
            $domain = $alias->getDomain();
            $alias->setSource($localPart."@".$domain->getName());
            $this->validateAndUpdate($alias);
        }

        return $alias;
    }
}
