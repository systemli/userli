<?php

namespace App\Command;

use App\Factory\VoucherFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class VoucherCreationCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $manager;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var string
     */
    private $appUrl;

    public function __construct(EntityManagerInterface $manager, RouterInterface $router, string $appUrl)
    {
        parent::__construct();

        $this->manager = $manager;
        $this->router = $router;
        $this->appUrl = $appUrl;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:voucher:create')
            ->setDescription('Create voucher for a specific user')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User who get the voucher(s)')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Count of the voucher which will created', 3)
            ->addOption('print', 'p', InputOption::VALUE_NONE, 'Print out vouchers')
            ->addOption('print-links', 'l', InputOption::VALUE_NONE, 'Print out links to vouchers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $email = $input->getOption('user');

        // Set
        $context = $this->router->getContext();
        $context->setBaseUrl($this->appUrl);

        if (empty($email) || null === $user = $this->manager->getRepository('App:User')->findByEmail($email)) {
            throw new UsernameNotFoundException(sprintf('User with email %s not found!', $email));
        }

        for ($i = 1; $i <= $input->getOption('count'); ++$i) {
            $voucher = VoucherFactory::create($user);
            if (true === $input->getOption('print-links')) {
                $output->write(sprintf("%s\n", $this->router->generate(
                    'register_voucher',
                    ['_locale' => 'en', 'voucher' => $voucher->getCode()]
                )));
            } elseif (true === $input->getOption('print')) {
                $output->write(sprintf("%s\n", $voucher->getCode()));
            }

            $this->manager->persist($voucher);
        }

        $this->manager->flush();
    }
}
