<?php

declare(strict_types=1);

namespace App\Controller;

use App\Creator\DomainCreator;
use App\Entity\Domain;
use App\Entity\Setting;
use App\Entity\User;
use App\Form\DomainCreateType;
use App\Form\InitUserType;
use App\Form\Model\DomainCreate;
use App\Form\Model\InitUser;
use App\Form\SettingsType;
use App\Helper\AdminPasswordUpdater;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InitController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly AdminPasswordUpdater $updater,
        private readonly DomainCreator $creator,
        private readonly SettingsService $settingsService,
    ) {
    }

    #[Route(path: '/init', name: 'init', methods: ['GET'])]
    public function init(): Response
    {
        // redirect if already configured
        if (0 < $this->manager->getRepository(Domain::class)->count([])) {
            return $this->redirectToRoute('init_user');
        }

        $form = $this->createForm(DomainCreateType::class, new DomainCreate(), [
            'action' => $this->generateUrl('init_submit'),
            'method' => 'post',
        ]);

        return $this->render('Init/domain.html.twig', ['form' => $form]);
    }

    #[Route(path: '/init', name: 'init_submit', methods: ['POST'])]
    public function initSubmit(Request $request): Response
    {
        $form = $this->createForm(DomainCreateType::class, new DomainCreate());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->creator->create($form->getData()->domain);

            return $this->redirectToRoute('init_user');
        }

        return $this->render('Init/domain.html.twig', ['form' => $form]);
    }

    #[Route(path: '/init/user', name: 'init_user', methods: ['GET'])]
    public function user(): Response
    {
        // redirect if already configured
        if (0 < $this->manager->getRepository(User::class)->count([])) {
            return $this->redirectToRoute('init_settings');
        }

        $form = $this->createForm(InitUserType::class, new InitUser(), [
            'action' => $this->generateUrl('init_user_submit'),
            'method' => 'post',
        ]);

        return $this->render('Init/user.html.twig', ['form' => $form]);
    }

    #[Route(path: '/init/user', name: 'init_user_submit', methods: ['POST'])]
    public function userSubmit(Request $request): Response
    {
        $form = $this->createForm(InitUserType::class, new InitUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->updater->updateAdminPassword($form->getData()->getPassword());

            $this->addFlash('success', 'flashes.password-change-successful');

            return $this->redirectToRoute('init_settings');
        }

        return $this->render('Init/user.html.twig', ['form' => $form]);
    }

    #[Route(path: '/init/settings', name: 'init_settings', methods: ['GET'])]
    public function settings(): Response
    {
        // redirect if already configured
        if (0 < $this->manager->getRepository(Setting::class)->count([])) {
            return $this->redirectToRoute('index');
        }

        // Get the first domain for default values
        $domain = $this->manager->getRepository(Domain::class)->findOneBy([]);
        $domainName = $domain?->getName() ?? '';

        // Prepare default data based on domain
        $defaultData = [
            'app_url' => $domainName ? 'https://users.'.$domainName : null,
            'project_url' => $domainName ? 'https://'.$domainName : null,
            'email_sender_address' => $domainName ? 'noreply@'.$domainName : null,
            'email_notification_address' => $domainName ? 'admin@'.$domainName : null,
        ];

        $form = $this->createForm(SettingsType::class, $defaultData, [
            'action' => $this->generateUrl('init_settings_submit'),
            'method' => 'post',
        ]);

        return $this->render('Init/settings.html.twig', [
            'form' => $form,
            'primary_domain' => $domainName,
        ]);
    }

    #[Route(path: '/init/settings', name: 'init_settings_submit', methods: ['POST'])]
    public function settingsSubmit(Request $request): Response
    {
        $form = $this->createForm(SettingsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Filter out null values and save only provided settings
            $data = array_filter($data, fn ($value) => $value !== null);

            $this->settingsService->setAll($data);

            $this->addFlash('success', 'init_settings.flash.configured_successfully');

            return $this->redirectToRoute('index');
        }

        return $this->render('Init/settings.html.twig', [
            'form' => $form,
        ]);
    }
}
