<?php

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

    #[Route(path: '/alias', name: 'aliases')]
    public function alias(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $randomAliasCreateForm = $this->createForm(
            RandomAliasCreateType::class,
            new AliasCreate(),
            [
                'action' => $this->generateUrl('aliases'),
                'method' => 'post',
            ]
        );

        $aliasCreate = new AliasCreate();
        $customAliasCreateForm = $this->createForm(
            CustomAliasCreateType::class,
            $aliasCreate,
            [
                'action' => $this->generateUrl('aliases'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $randomAliasCreateForm->handleRequest($request);
            $customAliasCreateForm->handleRequest($request);

            if ($randomAliasCreateForm->isSubmitted() && $randomAliasCreateForm->isValid()) {
                $this->createRandomAlias($request, $user);
                // force reload to not show bogus alias
                return $this->redirect($request->getUri());
            } elseif ($customAliasCreateForm->isSubmitted() && $customAliasCreateForm->isValid()) {
                $this->createCustomAlias($request, $user, $aliasCreate->alias);
            }
        }

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

    private function createRandomAlias(Request $request, User $user): void
    {
        try {
            if ($this->aliasHandler->create($user) instanceof Alias) {
                $request->getSession()->getFlashBag()->add('success', 'flashes.alias-creation-successful');
            }
        } catch (ValidationException $e) {
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());
        }
    }

    private function createCustomAlias(Request $request, User $user, string $alias): void
    {
        try {
            if ($this->aliasHandler->create($user, $alias) instanceof Alias) {
                $request->getSession()->getFlashBag()->add('success', 'flashes.alias-creation-successful');
            }
        } catch (ValidationException $e) {
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());
        }
    }
}
