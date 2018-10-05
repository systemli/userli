<?php

namespace App\Controller;

use App\Form\UserDeleteType;
use App\Form\Model\Delete;
use App\Handler\DeleteHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DeleteController.
 */
class DeleteController extends Controller
{
    /**
     * @var DeleteHandler
     */
    private $deleteHandler;

    /**
     * DeleteController constructor.
     *
     * @param DeleteHandler $deleteHandler
     */
    public function __construct(DeleteHandler $deleteHandler)
    {
        $this->deleteHandler = $deleteHandler;
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteUserAction(Request $request)
    {
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
            'User/delete.html.twig', [
                'form' => $form->createView(),
            ]
        );
    }
}
