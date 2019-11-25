<?php


namespace App\Controller;


use App\Entity\Domain;
use App\Entity\User;
use App\Enum\Roles;
use App\Form\Model\PlainPassword;
use App\Form\PlainPasswordType;
use App\Helper\PasswordUpdater;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class InitController
 * @package App\Controller
 */
class InitController extends AbstractController
{
    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var PasswordUpdater
     */
    private $passwordUpdater;
    /**
     * @var string
     */
    private $defaultDomain;

    /**
     * @param ObjectManager   $manager
     * @param PasswordUpdater $passwordUpdater
     * @param $defaultDomain
     */
    public function __construct (ObjectManager $manager, PasswordUpdater $passwordUpdater, $defaultDomain)
    {
        $this->manager = $manager;
        $this->passwordUpdater = $passwordUpdater;
        $this->defaultDomain = $defaultDomain;
    }

    /**
     * @param Request  $request
     *
     * @return Response
     */
    public function indexAction (Request $request)
    {
        // redirect if already configured
        if (0 < $this->manager->getRepository('App:Domain')->count([])) {
            return $this->redirectToRoute('index');
        }

        $password = new PlainPassword();
        $passwordForm = $this->createForm(
            PlainPasswordType::class,
            $password,
            [
                'action' => $this->generateUrl('init'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $passwordForm->handleRequest($request);

            if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
                // create primary domain and admin user
                $domain = new Domain();
                $domain->setName($this->defaultDomain);
                $admin = new User();
                $admin->setEmail('admin@'.$this->defaultDomain);
                $admin->setDomain($domain);
                $admin->setPlainPassword($password->newPassword);
                $admin->setRoles([Roles::ADMIN]);
                $this->passwordUpdater->updatePassword($admin);
                $this->manager->persist($domain);
                $this->manager->persist($admin);
                $this->manager->flush();
                $request->getSession()->getFlashBag()->add('success', 'flashes.password-change-successful');
                return $this->redirectToRoute('index');
            }
        }
        return $this->render('init.html.twig', ['form' => $passwordForm->createView()]);
    }

}