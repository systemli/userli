<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Alias;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Form\CustomAliasCreateType;
use App\Form\Model\AliasCreate;
use App\Form\RandomAliasCreateType;
use App\Handler\AliasHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AliasController extends AbstractController
{
    public function __construct(
        private readonly AliasHandler           $aliasHandler,
        private readonly EntityManagerInterface $manager,
    ) {}

    #[Route(path: '/alias', name: 'aliases', methods: ['GET'])]
    public function show(Request $request): Response
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
                'random_alias_form' => $randomAliasCreateForm->createView(),
                'custom_alias_form' => $customAliasCreateForm->createView(),
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
        } catch (ValidationException $e) {
            $this->addFlash('error', $e->getMessage());
        }
    }

    private function processCustomAliasCreation(User $user, string $alias): void
    {
        try {
            if ($this->aliasHandler->create($user, $alias) instanceof Alias) {
                $this->addFlash('success', 'flashes.alias-creation-successful');
            }
        } catch (ValidationException $e) {
            $this->addFlash('error', $e->getMessage());
        }
    }
}
