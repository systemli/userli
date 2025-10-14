<?php

namespace App\Controller;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PostfixController extends AbstractController
{
    public function __construct(readonly private EntityManagerInterface $manager)
    {
    }

    #[Route(path: '/api/postfix/alias/{rawAlias}', name: 'api_postfix_alias', methods: ['GET'], stateless: true)]
    public function getAliasUsers(string $rawAlias): Response
    {
        $alias = $this->normalizeEmailForLookup($rawAlias);
        $users = $this->manager->getRepository(Alias::class)->findBy(['deleted' => false, 'source' => $alias]);

        return $this->json(array_map(function (Alias $alias) {
            return $alias->getDestination();
        }, $users));
    }

    #[Route(path: '/api/postfix/domain/{name}', name: 'api_postfix_domain', methods: ['GET'], stateless: true)]
    public function getDomain(string $name): Response
    {
        $domain = $this->manager->getRepository(Domain::class)->findOneBy(['name' => $name]);
        $exists = $domain !== null;
        return $this->json($exists);
    }

    #[Route(path: '/api/postfix/mailbox/{rawEmail}', name: 'api_postfix_mailbox', methods: ['GET'], stateless: true)]
    public function getMailbox(string $rawEmail): Response
    {
        $email = $this->normalizeEmailForLookup($rawEmail);
        $user = $this->manager->getRepository(User::class)->findOneBy(['email' => $email, 'deleted' => false]);
        $exists = $user !== null;
        return $this->json($exists);
    }

    #[Route(path: '/api/postfix/senders/{email}', name: 'api_postfix_senders', methods: ['GET'], stateless: true)]
    public function getSenders(string $email): Response
    {
        $users = $this->manager->getRepository(User::class)->findBy(['deleted' => false, 'email' => $email]);
        $aliases = $this->manager->getRepository(Alias::class)->findBy(['deleted' => false, 'source' => $email]);

        // Extract email addresses from users
        $senders = array_map(function (User $user) {
            return $user->getEmail();
        }, $users);

        // Extract email addresses from alias destinations
        $senders = array_merge($senders, array_map(function (Alias $alias) {
            return $alias->getDestination();
        }, $aliases));

        // Remove duplicates
        $senders = array_unique($senders);

        return $this->json($senders);
    }

    /**
     * Normalize mail address by removing anything starting with "+" in the local part before performing lookup.
     */
    private function normalizeEmailForLookup(string $email): string
    {
        $atPos = strpos($email, '@');
        if ($atPos === false) {
            return $email;
        }
        $local = substr($email, 0, $atPos);
        $domain = substr($email, $atPos + 1);
        $plusPos = strpos($local, '+');
        if ($plusPos !== false) {
            $local = substr($local, 0, $plusPos);
        }
        return $local . '@' . $domain;
    }
}
