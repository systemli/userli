<?php

namespace App\Controller;

use App\Creator\DomainCreator;
use App\Entity\Domain;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Form\DomainCreateType;
use App\Form\Model\DomainCreate;
use App\Form\Model\PlainPassword;
use App\Form\PlainPasswordType;
use App\Helper\AdminPasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class InitController.
 */
class InitController extends AbstractController
{
    public function __construct(private EntityManagerInterface $manager, private AdminPasswordUpdater $updater, private DomainCreator $creator)
    {
    }

    /**
     * @throws ValidationException
     */
    public function indexAction(Request $request): Response
    {
        // redirect if already configured
        if (0 < $this->manager->getRepository(Domain::class)->count([])) {
            return $this->redirectToRoute('init_user');
        }

        $domain = new DomainCreate();
        $domainForm = $this->createForm(
            DomainCreateType::class,
            $domain,
            [
                'action' => $this->generateUrl('init'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $domainForm->handleRequest($request);

            if ($domainForm->isSubmitted() && $domainForm->isValid()) {
                $this->creator->create($domain->domain);

                return $this->redirectToRoute('init_user');
            }
        }

        return $this->render('Init/domain.html.twig', ['form' => $domainForm->createView()]);
    }

    public function userAction(Request $request): Response
    {
        // redirect if already configured
        if (0 < $this->manager->getRepository(User::class)->count([])) {
            return $this->redirectToRoute('index');
        }

        $password = new PlainPassword();
        $passwordForm = $this->createForm(
            PlainPasswordType::class,
            $password,
            [
                'action' => $this->generateUrl('init_user'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $passwordForm->handleRequest($request);

            if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
                $this->updater->updateAdminPassword($password->getPlainPassword());
                $request->getSession()->getFlashBag()->add('success', 'flashes.password-change-successful');

                return $this->redirectToRoute('index');
            }
        }

        return $this->render('Init/user.html.twig', ['form' => $passwordForm->createView()]);
    }
}
