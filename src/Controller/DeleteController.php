<?php

namespace App\Controller;

use App\Form\DeleteType;
use App\Form\Model\Delete;
use App\Handler\DeleteHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DeleteController
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request)
    {
        $form = $this->createForm(DeleteType::class, new Delete());

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $user = $this->getUser();

                $this->deleteHandler->deleteUser($user);

                return $this->redirect($this->generateUrl('logout'));
            }
        }

        return $this->render(
            'Delete/delete.html.twig', [
                'form' => $form->createView(),
            ]
        );
    }
}
