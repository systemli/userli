<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Voucher;
use App\Event\Events;
use App\Event\UserEvent;
use App\Form\Model\PasswordChange;
use App\Form\Model\VoucherCreate;
use App\Form\PasswordChangeType;
use App\Form\VoucherCreateType;
use App\Helper\PasswordUpdater;
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
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->render('Start/index_anonymous.html.twig');
        }

        /**
         * @var User $user
         */
        $user = $this->getUser();

        $voucherRepository = $this->get('doctrine')->getRepository('App:Voucher');
        $vouchers = $voucherRepository->findOrCreateByUser($user);

        $voucherCreateForm = $this->getVoucherCreateForm();

        $passwordChange = new PasswordChange();
        $passwordChangeForm = $this->getPasswordChangeForm($passwordChange);

        return $this->render('Start/index.html.twig', array(
            'user' => $user,
            'vouchers' => $vouchers,
            'voucher_form' => $voucherCreateForm->createView(),
            'password_form' => $passwordChangeForm->createView(),
        ));
    }

    public function voucherCreateAction(Request $request)
    {
        if ('POST' === $request->getMethod()) {
            /**
             * @var User $user
             */
            $user = $this->getUser();

            $voucherCreateForm = $this->getVoucherCreateForm();

            $voucherRepository = $this->get('doctrine')->getRepository('App:Voucher');

            $voucherCreateForm->handleRequest($request);

            if ($voucherCreateForm->isSubmitted() && $voucherCreateForm->isValid()) {
                if ($this->isGranted('ROLE_SUPPORT')) {
                    $voucher = $voucherRepository->createByUser($user);

                    if ($voucher instanceof Voucher) {
                        $request->getSession()->getFlashBag()->add('success', 'flashes.voucher-creation-successful');
                    }
                }
                return $this->redirect($this->generateUrl('index'));
            }
        }

        return $this->redirect($this->generateUrl('index'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function passwordChangeAction(Request $request)
    {
        if ('POST' === $request->getMethod()) {
            /**
             * @var User $user
             */
            $user = $this->getUser();

            $passwordChange = new PasswordChange();
            $passwordChangeForm = $this->getPasswordChangeForm($passwordChange);

            $passwordChangeForm->handleRequest($request);

            if ($passwordChangeForm->isSubmitted() && $passwordChangeForm->isValid()) {
                $user->setPlainPassword($passwordChange->newPassword);

                $this->get(PasswordUpdater::class)->updatePassword($user);

                $this->getDoctrine()->getManager()->flush();

                $this->get('event_dispatcher')->dispatch(
                    Events::MAIL_ACCOUNT_PASSWORD_CHANGED,
                    new UserEvent($user)
                );

                $request->getSession()->getFlashBag()->add('success', 'flashes.password-change-successful');
            }
        }

        return $this->redirect($this->generateUrl('index'));
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    private function getVoucherCreateForm()
    {
        $form = $this->createForm(
            VoucherCreateType::class,
            new VoucherCreate(),
            [
                'action' => $this->generateUrl('voucher_new'),
                'method' => 'post',
            ]
        );
        return $form;
    }

    /**
     * @param PasswordChange $passwordChange
     * @return \Symfony\Component\Form\FormInterface
     */
    private function getPasswordChangeForm(PasswordChange $passwordChange)
    {
        $form = $this->createForm(
            PasswordChangeType::class,
            $passwordChange,
            [
                'action' => $this->generateUrl('password_change'),
                'method' => 'post',
            ]
        );
        return $form;
    }
}
