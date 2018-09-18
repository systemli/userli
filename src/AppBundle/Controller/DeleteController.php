<?php

namespace AppBundle\Controller;

use AppBundle\Form\DeleteType;
use AppBundle\Form\Model\Delete;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DeleteController extends Controller
{
    public function deleteAction(Request $request)
    {
        $form = $this->createForm(DeleteType::class, new Delete());

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $user = $this->get('security.token_storage')->getToken()->getUser();

                $this->get('AppBundle\Handler\DeleteHandler')->deleteUser($user);

                return $this->redirect('logout');
            }
        }

        return $this->render('Delete/delete.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
