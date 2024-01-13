<?php

namespace App\Controller;

use App\Entity\Alias;
use App\Form\AliasDeleteType;
use App\Form\Model\Delete;
use App\Form\OpenPgpDeleteType;
use App\Form\UserDeleteType;
use App\Handler\DeleteHandler;
use App\Handler\WkdHandler;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeleteController extends AbstractController
{
    public function __construct(private DeleteHandler $deleteHandler, private WkdHandler $wkdHandler, private ManagerRegistry $doctrine)
    {
    }

    /**
     * @Route("/{_locale}/alias/delete/{aliasId}", name="alias_delete", requirements={"_locale": "%locales%"})
     * @Route("/alias/delete/{aliasId}", name="alias_delete_fallback")
     * @param Request $request
     * @param $aliasId
     * @return Response
     */
    public function deleteAlias(Request $request, $aliasId): Response
    {
        $user = $this->getUser();
        $aliasRepository = $this->doctrine->getRepository(Alias::class);
        $alias = $aliasRepository->find($aliasId);

        // Don't allow users to delete custom or foreign aliases
        if (null === $alias || $user !== $alias->getUser() || !$alias->isRandom()) {
            return $this->redirect($this->generateUrl('aliases'));
        }

        $form = $this->createForm(AliasDeleteType::class, new Delete());

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->deleteHandler->deleteAlias($alias, $user);

                $request->getSession()->getFlashBag()->add('success', 'flashes.alias-deletion-successful');

                return $this->redirect($this->generateUrl('aliases'));
            }
        }

        return $this->render(
            'Alias/delete.html.twig',
            [
                'alias' => $alias,
                'form' => $form->createView(),
                'user' => $user,
            ]
        );
    }

    /**
     * @Route("/{_locale}/user/delete", name="user_delete", requirements={"_locale": "%locales%"})
     * @Route("/user/delete", name="user_delete_fallback")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function deleteUser(Request $request): RedirectResponse|Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserDeleteType::class, new Delete());

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $user = $this->getUser();

                $this->deleteHandler->deleteUser($user);

                return $this->redirect($this->generateUrl('logout'));
            }
        }

        return $this->render(
            'User/delete.html.twig',
            [
                'form' => $form->createView(),
                'user' => $user,
            ]
        );
    }

    /**
     * @Route("/{_locale}/openpgp/delete", name="openpgp_delete", requirements={"_locale": "%locales%"})
     * @Route("/openpgp/delete", name="openpgp_delete_fallback")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function deleteOpenPgp(Request $request): RedirectResponse|Response
    {
        $user = $this->getUser();

        $form = $this->createForm(OpenPgpDeleteType::class, new Delete());

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->wkdHandler->deleteKey($user->getEmail());

                $request->getSession()->getFlashBag()->add('success', 'flashes.openpgp-deletion-successful');

                return $this->redirect($this->generateUrl('openpgp'));
            }
        }

        return $this->render(
            'OpenPgp/delete.html.twig',
            [
                'form' => $form->createView(),
                'user' => $user,
            ]
        );
    }
}
