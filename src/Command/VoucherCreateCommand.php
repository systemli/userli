<?php

declare(strict_types=1);

namespace App\Command;

use App\Creator\VoucherCreator;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'app:voucher:create', description: 'Create voucher for a specific user')]
class VoucherCreateCommand extends AbstractUsersCommand
{
    public function __construct(
        EntityManagerInterface $manager,
        private readonly RouterInterface $router,
        private readonly VoucherCreator $creator,
        private readonly SettingsService $settingsService,
    ) {
        parent::__construct($manager);
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'How many voucher to create', 3)
            ->addOption('print', 'p', InputOption::VALUE_NONE, 'Show vouchers')
            ->addOption('print-links', 'l', InputOption::VALUE_NONE, 'Show links to vouchers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->getUser($input);

        // Set
        $context = $this->router->getContext();
        $context->setBaseUrl($this->settingsService->get('app_url'));

        for ($i = 1; $i <= $input->getOption('count'); ++$i) {
            $voucher = $this->creator->create($user);
            if (true === $input->getOption('print-links')) {
                $output->write(sprintf("%s\n", $this->router->generate(
                    'register_voucher',
                    ['voucher' => $voucher->getCode()]
                )));
            } elseif (true === $input->getOption('print')) {
                $output->write(sprintf("%s\n", $voucher->getCode()));
            }
        }

        return 0;
    }
}
