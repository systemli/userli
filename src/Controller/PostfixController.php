<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Enum\ApiScope;
use App\Security\RequireApiScope;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[RequireApiScope(scope: ApiScope::POSTFIX)]
final class PostfixController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly SettingsService $settingsService,
    ) {
    }

    #[Route(path: '/api/postfix/alias/{alias}', name: 'api_postfix_get_alias_users', methods: ['GET'], stateless: true)]
    public function getAliasUsers(string $alias): Response
    {
        $users = $this->manager->getRepository(Alias::class)->findBy(['deleted' => false, 'source' => $alias]);

        return $this->json(array_map(function (Alias $alias) {
            return $alias->getDestination();
        }, $users));
    }

    #[Route(path: '/api/postfix/domain/{name}', name: 'api_postfix_get_domain', methods: ['GET'], stateless: true)]
    public function getDomain(string $name): Response
    {
        $domain = $this->manager->getRepository(Domain::class)->findOneBy(['name' => $name]);
        $exists = $domain !== null;

        return $this->json($exists);
    }

    #[Route(path: '/api/postfix/mailbox/{email}', name: 'api_postfix_get_mailbox', methods: ['GET'], stateless: true)]
    public function getMailbox(string $email): Response
    {
        $user = $this->manager->getRepository(User::class)->findOneBy(['email' => $email, 'deleted' => false]);
        $exists = $user !== null;

        return $this->json($exists);
    }

    #[Route(path: '/api/postfix/senders/{email}', name: 'api_postfix_get_senders', methods: ['GET'], stateless: true)]
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

    #[Route(path: '/api/postfix/quota/{email}', name: 'api_postfix_get_smtp_quota', methods: ['GET'], stateless: true)]
    public function getSmtpQuota(string $email): Response
    {
        $user = $this->manager->getRepository(User::class)
            ->findOneBy(['email' => $email, 'deleted' => false]);

        if ($user !== null) {
            $limits = $user->getSmtpQuotaLimits() ?? [];
        } else {
            $alias = $this->manager->getRepository(Alias::class)
                ->findOneBy(['source' => $email, 'deleted' => false]);

            if ($alias === null) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $limits = $alias->getSmtpQuotaLimits() ?? [];
        }

        return $this->json([
            'per_hour' => $limits['per_hour'] ?? (int) $this->settingsService->get('smtp_quota_limit_per_hour', 0),
            'per_day' => $limits['per_day'] ?? (int) $this->settingsService->get('smtp_quota_limit_per_day', 0),
        ]);
    }
}
