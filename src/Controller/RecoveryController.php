<?php

namespace App\Controller;

use App\Form\Model\RecoveryProcess;
use App\Form\Model\RecoveryToken;
use App\Form\RecoveryProcessType;
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
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function recoveryProcessAction(Request $request)
    {
        $processState = 'NONE';
        $form = $this->createForm(RecoveryProcessType::class, new RecoveryProcess());

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $userRepository = $this->get('doctrine')->getRepository('App:User');

                if (null !== $user = $userRepository->findByEmail($request->get('recovery_process')['username'])) {
                    $recoveryProcessTime = $user->getRecoveryProcessTime();

                    if (null === $recoveryProcessTime || new \DateTime('-30 days') >= $recoveryProcessTime) {
                        $processState = 'STARTED';
                        $user->updateRecoveryProcessTime();
                        $this->getDoctrine()->getManager()->flush();
                    } else if (new \DateTime('-2 days') <= $recoveryProcessTime) {
                        $processState = 'PENDING';
                    } else {
                        $processState = 'ACTIVE';
                        //TODO: allow to change password

                    }
                }
            }
        }

        return $this->render(
            'Recovery/recovery.html.twig',
            [
                'form' => $form->createView(),
                'process_state' => $processState,
            ]
        );
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
                        'recovery_token_set' => $user->hasRecoveryToken(),
                    ]
                );
            }
        }

        return $this->render('User/recovery_token.html.twig',
            [
                'form' => $form->createView(),
                'recovery_token_set' => $user->hasRecoveryToken(),
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
