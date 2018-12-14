<?php

namespace App\Controller;

use App\Form\Model\RecoveryToken;
use App\Form\RecoveryTokenType;
use App\Handler\RecoveryTokenHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author doobry <doobry@systemli.org>
 */
class RecoveryController extends Controller
{
    public function recoveryProcessAction(Request $request)
    {
        return $this->render('Start/index_anonymous.html.twig');
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function recoveryTokenAction(Request $request)
    {
        $user = $this->getUser();
        $recoveryTokenHandler = $this->getRecoveryTokenHandler();

        $form = $this->createForm(RecoveryTokenType::class, new RecoveryToken());

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $user->setPlainPassword($request->get('recovery_token')['password']);
                $recoveryToken = $recoveryTokenHandler->create($user);

                return $this->render('User/recovery_token.html.twig',
                    [
                        'form' => $form->createView(),
                        'recovery_token' => $recoveryToken,
                        'recovery_token_set' => $recoveryTokenHandler->hasToken($user),
                    ]
                );
            }
        }

        return $this->render('User/recovery_token.html.twig',
            [
                'form' => $form->createView(),
                'recovery_token_set' => $recoveryTokenHandler->hasToken($user),
            ]
        );
    }

    /**
     * @return RecoveryTokenHandler
     */
    private function getRecoveryTokenHandler()
    {
        return $this->get('App\Handler\RecoveryTokenHandler');
    }
}
