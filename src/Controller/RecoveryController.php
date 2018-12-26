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
    public function recoveryProcessAction(Request $request): Response
    {
        $processState = 'NONE';
        $form = $this->createForm(RecoveryProcessType::class, new RecoveryProcess());

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $userRepository = $this->get('doctrine')->getRepository('App:User');

                if (null !== $user = $userRepository->findByEmail($request->get('recovery_process')['username'])) {
                    $recoveryStartTime = $user->getRecoveryStartTime();

                    if (null === $recoveryStartTime || new \DateTime('-30 days') >= $recoveryStartTime) {
                        $processState = 'STARTED';
                        $user->updateRecoveryStartTime();
                        $this->getDoctrine()->getManager()->flush();
                    } else if (new \DateTime('-2 days') <= $recoveryStartTime) {
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
    public function recoveryTokenAction(Request $request): Response
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
                        'recovery_secret_set' => $user->hasRecoverySecret(),
                    ]
                );
            }
        }

        return $this->render('User/recovery_token.html.twig',
            [
                'form' => $form->createView(),
                'recovery_secret_set' => $user->hasRecoverySecret(),
            ]
        );
    }

    /**
     * @return RecoveryTokenHandler
     */
    private function getRecoveryTokenHandler(): RecoveryTokenHandler
    {
        return $this->get('App\Handler\RecoveryTokenHandler');
    }
}
