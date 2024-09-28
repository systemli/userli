<?php

namespace App\Controller;

use App\Exception\ValidationException;
use App\Form\AliasType;
use App\Form\BasicRegistrationType;
use App\Form\Model\Alias;
use App\Form\Model\BasicRegistration;
use App\Handler\AliasHandler;
use App\Handler\RegistrationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DomainSettingsController extends AbstractController
{
    public function __construct(private readonly RegistrationHandler $registrationHandler, private readonly AliasHandler $aliasHandler)
    {
    }

    #[Route(path: '/domain/settings', name: 'domain_settings', methods: ['GET'])]
    public function getDomainSettings(): Response
    {
        $registration = $this->createForm(
            BasicRegistrationType::class,
            new BasicRegistration(),
            [
                'action' => $this->generateUrl('domain_settings_account'),
                'method' => 'POST'
            ]
        );
        $alias = $this->createForm(
            AliasType::class,
            new Alias(),
            [
                'action' => $this->generateUrl('domain_settings_alias'),
                'method' => 'POST'
            ]
        );

        return $this->render('DomainSettings/index.html.twig', [
            'registration' => $registration->createView(),
            'alias' => $alias->createView(),
        ]);
    }

    #[Route(path: '/domain/settings/account', name: 'domain_settings_account', methods: ['POST'])]
    public function postDomainSettingsAccount(Request $request): Response
    {
        $form = $this->createForm(BasicRegistrationType::class, new BasicRegistration());
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'domain_settings.form-error');

            return $this->render('DomainSettings/index.html.twig', [
                'registration' => $form->createView(),
            ]);
        }

        try {
            $data = $form->getData();
            $this->registrationHandler->handle($data->getEmail(), $data->getPlainPassword());
            $this->addFlash('success', 'domain_settings.registration-success');
        } catch (\Exception) {
            $this->addFlash('error', 'domain_settings.form-error');
        }

        return $this->redirectToRoute('domain_settings');
    }

    #[Route(path: '/domain/settings/alias', name: 'domain_settings_alias', methods: ['POST'])]
    public function postDomainSettingsAlias(Request $request): Response
    {
        $form = $this->createForm(AliasType::class, new Alias());
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'domain_settings.form-error');

            return $this->render('DomainSettings/index.html.twig', [
                'alias' => $form->createView(),
            ]);
        }

        try {
            $data = $form->getData();
            $this->aliasHandler->create($data->getUser(), $data->getAlias());
            $this->addFlash('success', 'domain_settings.alias-success');
        } catch (ValidationException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('domain_settings');
    }
}
