<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Domain;
use App\Service\SettingsService;
use App\Service\VoucherManager;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'app:voucher:create', description: 'Create voucher for a specific user')]
final class VoucherCreateCommand extends AbstractUsersCommand
{
    public function __construct(
        EntityManagerInterface $manager,
        private readonly RouterInterface $router,
        private readonly VoucherManager $voucherManager,
        private readonly SettingsService $settingsService,
    ) {
        parent::__construct($manager);
    }

    #[Override]
    protected function configure(): void
    {
        parent::configure();
        $this
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'How many voucher to create', 3)
            ->addOption('print', 'p', InputOption::VALUE_NONE, 'Show vouchers')
            ->addOption('print-links', 'l', InputOption::VALUE_NONE, 'Show links to vouchers')
            ->addOption('domain', 'd', InputOption::VALUE_OPTIONAL, 'Domain for the voucher (default: user domain)');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->getUser($input, $output);
        if (null === $user) {
            return Command::FAILURE;
        }

        $domainName = $input->getOption('domain');
        if (null !== $domainName) {
            $domain = $this->manager->getRepository(Domain::class)->findByName($domainName);
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

        for ($i = 1; $i <= $input->getOption('count'); ++$i) {
            $voucher = $this->voucherManager->create($user, $domain);
            if (true === $input->getOption('print-links')) {
                $output->write(sprintf("%s\n", $this->router->generate(
                    'register_voucher',
                    ['voucher' => $voucher->getCode()]
                )));
            } elseif (true === $input->getOption('print')) {
                $output->write(sprintf("%s\n", $voucher->getCode()));
            }
        }

        return Command::SUCCESS;
    }
}
