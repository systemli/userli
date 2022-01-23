<?php

namespace App\Controller;

use App\Form\AliasDeleteType;
use App\Form\Model\Delete;
use App\Form\OpenPgpDeleteType;
use App\Form\UserDeleteType;
use App\Handler\DeleteHandler;
use App\Handler\WkdHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DeleteController.
 */
class DeleteController extends AbstractController
{
    /**
     * @var DeleteHandler
     */
    private $deleteHandler;
    /**
     * @var WkdHandler
     */
    private $wkdHandler;

    /**
     * DeleteController constructor.
     */
    public function __construct(DeleteHandler $deleteHandler, WkdHandler $wkdHandler)
    {
        $this->deleteHandler = $deleteHandler;
        $this->wkdHandler = $wkdHandler;
    }

    /**
     * @param $aliasId
     *
     * @return RedirectResponse|Response
     */
    public function deleteAliasAction(Request $request, $aliasId)
    {
        $user = $this->getUser();
        $aliasRepository = $this->get('doctrine')->getRepository('App:Alias');
        $alias = $aliasRepository->find($aliasId);

        // Don't allow users to delete custom or foreign aliases
        if (null === $alias || $user !== $alias->getUser() || !($alias->isRandom())) {
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
     * @return RedirectResponse|Response
     */
    public function deleteUserAction(Request $request)
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

    public function deleteOpenPgpAction(Request $request)
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
