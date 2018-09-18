<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Entity\Voucher;
use AppBundle\Event\Events;
use AppBundle\Event\UserEvent;
use AppBundle\Form\Model\PasswordChange;
use AppBundle\Form\Model\VoucherCreate;
use AppBundle\Form\PasswordChangeType;
use AppBundle\Form\VoucherCreateType;
use AppBundle\Helper\PasswordUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author louis <louis@systemli.org>
 */
class StartController extends Controller
{
    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function indexAction(Request $request)
    {
        $authChecker = $this->container->get('security.authorization_checker');

        if ($authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            /** @var User $user */
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $voucherRepository = $this->get('doctrine')->getRepository('AppBundle:Voucher');

            $codes = $voucherRepository->findOrCreateByUser($user);

            $passwordChange = new PasswordChange();
            $form = $this->createForm(
                PasswordChangeType::class,
                $passwordChange,
                [
                    'action' => $this->generateUrl('index'),
                    'method' => 'post',
                ]
            );

            $voucherForm = $this->createForm(
                VoucherCreateType::class,
                new VoucherCreate(),
                [
                    'action' => $this->generateUrl('index'),
                    'method' => 'post',
                ]
            );

            if ('POST' === $request->getMethod()) {
                if ($request->request->has('create_voucher')) {
                    $voucherForm->handleRequest($request);

                    if ($voucherForm->isSubmitted() && $voucherForm->isValid()) {
                        if ($authChecker->isGranted('ROLE_SUPPORT')) {
                            $voucher = $voucherRepository->createByUser($user);

                            if ($voucher instanceof Voucher) {
                                $request->getSession()->getFlashBag()->add('success', 'flashes.voucher-creation-successful');
                            }
                        }

                        return $this->redirect($this->generateUrl('index'));
                    }
                } else {
                    $form->handleRequest($request);

                    if ($form->isSubmitted() && $form->isValid()) {
                        $user->setPlainPassword($passwordChange->newPassword);

                        $this->get(PasswordUpdater::class)->updatePassword($user);

                        $this->getDoctrine()->getManager()->flush();

                        $this->get('event_dispatcher')->dispatch(
                            Events::MAIL_ACCOUNT_PASSWORD_CHANGED,
                            new UserEvent($user)
                        );

                        $request->getSession()->getFlashBag()->add('success', 'flashes.password-change-successful');

                        return $this->redirect($this->generateUrl('index'));
                    }
                }
            }

            return $this->render('Start/index.html.twig', array(
                'user' => $user,
                'codes' => $codes,
                'form' => $form->createView(),
                'voucher_form' => $voucherForm->createView(),
            ));
        } else {
            return $this->render('Start/index_anonymous.html.twig');
        }
    }
}
