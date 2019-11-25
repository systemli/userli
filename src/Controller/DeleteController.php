<?php

namespace App\Controller;

use App\Form\AliasDeleteType;
use App\Form\Model\Delete;
use App\Form\UserDeleteType;
use App\Handler\DeleteHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

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
     * DeleteController constructor.
     */
    public function __construct(DeleteHandler $deleteHandler)
    {
        $this->deleteHandler = $deleteHandler;
    }

    /**
     * @param $aliasId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
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
}
