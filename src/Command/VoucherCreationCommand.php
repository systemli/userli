<?php

namespace App\Command;

use App\Factory\VoucherFactory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * @author louis <louis@systemli.org>
 */
class VoucherCreationCommand extends ContainerAwareCommand
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var string
     */
    private $appUrl;

    /**
     * VoucherCreationCommand constructor.
     *
     * @param RouterInterface $router
     * @param string          $appUrl
     */
    public function __construct(RouterInterface $router, string $appUrl)
    {
        parent::__construct();

        $this->router = $router;
        $this->appUrl = $appUrl;
    }

    protected function configure()
    {
        $this
            ->setName('app:voucher:create')
            ->setDescription('Create voucher for a specific user')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User who get the voucher(s)')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Count of the voucher which will created', 3)
            ->addOption('print', 'p', InputOption::VALUE_NONE, 'Print out vouchers')
            ->addOption('print-links', 'l', InputOption::VALUE_NONE, 'Print out links to vouchers');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = $input->getOption('user');
        $manager = $this->getContainer()->get('doctrine')->getManager();

        // Set
        $context = $this->router->getContext();
        $context->setBaseUrl($this->appUrl);

        if (empty($email) || null === $user = $manager->getRepository('App:User')->findByEmail($email)) {
            throw new UsernameNotFoundException(sprintf('User with email %s not found!', $email));
        }

        $manager = $this->getContainer()->get('doctrine')->getManager();

        for ($i = 1; $i <= $input->getOption('count'); ++$i) {
            $voucher = VoucherFactory::create($user);
            if (true === $input->getOption('print-links')) {
                $output->write(sprintf("%s\n", $this->router->generate('register_voucher', ['voucher' => $voucher->getCode()])));
            } elseif (true === $input->getOption('print')) {
                $output->write(sprintf("%s\n", $voucher->getCode()));
            }

            $manager->persist($voucher);
        }

        $manager->flush();
    }
}
