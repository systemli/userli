<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use App\Service\SettingsService;
use App\Service\VoucherManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'app:voucher:create', description: 'Create voucher for a specific user')]
final readonly class VoucherCreateCommand
{
    public function __construct(
        private UserRepository $userRepository,
        private DomainRepository $domainRepository,
        private RouterInterface $router,
        private VoucherManager $voucherManager,
        private SettingsService $settingsService,
    ) {
    }

    public function __invoke(
        #[Option(name: 'user', description: 'User to act upon', shortcut: 'u')]
        ?string $email = null,
        #[Option(description: 'How many voucher to create', shortcut: 'c')]
        int $count = 3,
        #[Option(description: 'Show vouchers', shortcut: 'p')]
        bool $print = false,
        #[Option(name: 'print-links', description: 'Show links to vouchers', shortcut: 'l')]
        bool $printLinks = false,
        #[Option(name: 'domain', description: 'Domain for the voucher (default: user domain)', shortcut: 'd')]
        ?string $domainName = null,
        ?OutputInterface $output = null,
    ): int {
        if (empty($email) || null === $user = $this->userRepository->findByEmail($email)) {
            $output->writeln(sprintf('<error>User with email %s not found!</error>', $email));

            return Command::FAILURE;
        }

        if (null !== $domainName) {
            $domain = $this->domainRepository->findByName($domainName);
            if (null === $domain) {
                $output->writeln(sprintf('<error>Domain %s not found!</error>', $domainName));

                return Command::FAILURE;
            }
        } else {
            $domain = $user->getDomain();
        }

        // Set
        $context = $this->router->getContext();
        $context->setBaseUrl($this->settingsService->get('app_url'));

        for ($i = 1; $i <= $count; ++$i) {
            $voucher = $this->voucherManager->create($user, $domain);
            if ($printLinks) {
                $output->write(sprintf("%s\n", $this->router->generate(
                    'register_voucher',
                    ['voucher' => $voucher->getCode()]
                )));
            } elseif ($print) {
                $output->write(sprintf("%s\n", $voucher->getCode()));
            }
        }

        return Command::SUCCESS;
    }
}
