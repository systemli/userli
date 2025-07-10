<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Alias;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Form\AliasDeleteType;
use App\Form\CustomAliasCreateType;
use App\Form\Model\AliasCreate;
use App\Form\Model\Delete;
use App\Form\RandomAliasCreateType;
use App\Handler\AliasHandler;
use App\Handler\DeleteHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AliasController extends AbstractController
{
    public function __construct(
        private readonly AliasHandler           $aliasHandler,
        private readonly DeleteHandler          $deleteHandler,
        private readonly EntityManagerInterface $manager,
    )
    {
    }

    #[Route(path: '/alias', name: 'aliases', methods: ['GET'])]
    public function show(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $randomAliasCreateForm = $this->createForm(
            RandomAliasCreateType::class,
            new AliasCreate(),
            [
                'action' => $this->generateUrl('aliases_create'),
                'method' => 'post',
            ]
        );

        $customAliasCreateForm = $this->createForm(
            CustomAliasCreateType::class,
            new AliasCreate(),
            [
                'action' => $this->generateUrl('aliases_create'),
                'method' => 'post',
            ]
        );

        $aliasRepository = $this->manager->getRepository(Alias::class);
        $aliasesRandom = $aliasRepository->findByUser($user, true, true);
        $aliasesCustom = $aliasRepository->findByUser($user, false, true);

        return $this->render(
            'Start/aliases.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
                'alias_creation_random' => $this->aliasHandler->checkAliasLimit($aliasesRandom, true),
                'alias_creation_custom' => $this->aliasHandler->checkAliasLimit($aliasesCustom),
                'aliases_custom' => $aliasesCustom,
                'aliases_random' => $aliasesRandom,
                'random_alias_form' => $randomAliasCreateForm,
                'custom_alias_form' => $customAliasCreateForm,
            ]
        );
    }

    #[Route(path: '/alias/create', name: 'aliases_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $randomAliasCreateForm = $this->createForm(RandomAliasCreateType::class, new AliasCreate());
        $aliasCreate = new AliasCreate();
        $customAliasCreateForm = $this->createForm(CustomAliasCreateType::class, $aliasCreate);

        $randomAliasCreateForm->handleRequest($request);
        $customAliasCreateForm->handleRequest($request);

        if ($randomAliasCreateForm->isSubmitted() && $randomAliasCreateForm->isValid()) {
            $this->processRandomAliasCreation($user);
        } elseif ($customAliasCreateForm->isSubmitted() && $customAliasCreateForm->isValid()) {
            $this->processCustomAliasCreation($user, $aliasCreate->alias);
        }

        return $this->redirectToRoute('aliases');
    }

    private function processRandomAliasCreation(User $user): void
    {
        try {
            if ($this->aliasHandler->create($user) instanceof Alias) {
                $this->addFlash('success', 'flashes.alias-creation-successful');
            }
        } catch (ValidationException $validationException) {
            $this->addFlash('error', $validationException->getMessage());
        }
    }

    private function processCustomAliasCreation(User $user, string $alias): void
    {
        try {
            if ($this->aliasHandler->create($user, $alias) instanceof Alias) {
                $this->addFlash('success', 'flashes.alias-creation-successful');
            }
        } catch (ValidationException $validationException) {
            $this->addFlash('error', $validationException->getMessage());
        }
    }

    #[Route(path: '/alias/delete/{id}', name: 'alias_delete', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('delete', subject: 'alias')]
    public function delete(
        #[MapEntity(class: Alias::class, expr: 'repository.findOneBy({id: id, deleted: false})')]
        Alias $alias): Response
    {
        $form = $this->createForm(
            AliasDeleteType::class,
            new Delete(),
            [
                'action' => $this->generateUrl('alias_delete_submit', ['id' => $alias->getId()]),
                'method' => 'post',
            ]
        );

        return $this->render(
            'Alias/delete.html.twig',
            [
                'alias' => $alias,
                'form' => $form,
                'user' => $this->getUser(),
            ]
        );
    }

    #[Route(path: '/alias/delete/{id}', name: 'alias_delete_submit', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('delete', subject: 'alias')]
    public function deleteSubmit(
        Request $request,
        #[MapEntity(class: Alias::class, expr: 'repository.findOneBy({id: id, deleted: false})')]
        Alias   $alias): RedirectResponse|Response
    {
        $form = $this->createForm(AliasDeleteType::class, new Delete());
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->deleteHandler->deleteAlias($alias, $this->getUser());

            $request->getSession()->getFlashBag()->add('success', 'flashes.alias-deletion-successful');

            return $this->redirectToRoute('aliases');
        }

        return $this->render(
            'Alias/delete.html.twig',
            [
                'alias' => $alias,
                'form' => $form,
                'user' => $this->getUser(),
            ]
        );
    }
}
