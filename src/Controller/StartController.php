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
use App\Repository\VoucherRepository;
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
     * @return Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function indexAction(Request $request)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->render('Start/index_anonymous.html.twig');
        }

        /** @var User $user */
        $user = $this->getUser();

        $voucherRepository = $this->get('doctrine')->getRepository('App:Voucher');
        $vouchers = $voucherRepository->findOrCreateByUser($user);

        $voucherCreateForm = $this->createForm(
            VoucherCreateType::class,
            new VoucherCreate(),
            [
                'action' => $this->generateUrl('index'),
                'method' => 'post',
            ]
        );

        $passwordChange = new PasswordChange();
        $passwordChangeForm = $this->createForm(
            PasswordChangeType::class,
            $passwordChange,
            [
                'action' => $this->generateUrl('index'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $voucherCreateForm->handleRequest($request);
            $passwordChangeForm->handleRequest($request);

            if ($voucherCreateForm->isSubmitted() && $voucherCreateForm->isValid()) {
                $this->voucherCreateAction($request, $user, $voucherRepository);
            } elseif ($passwordChangeForm->isSubmitted() && $passwordChangeForm->isValid()) {
                $this->passwordChangeAction($request, $user, $passwordChange);
            }
        }

        return $this->render('Start/index.html.twig', array(
            'user' => $user,
            'vouchers' => $vouchers,
            'voucher_form' => $voucherCreateForm->createView(),
            'password_form' => $passwordChangeForm->createView(),
        ));
    }

    /**
     * @param Request $request
     * @param User $user
     * @param VoucherRepository $voucherRepository
     * @return RedirectResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function voucherCreateAction(Request $request, User $user, VoucherRepository $voucherRepository)
    {
        if ($this->isGranted('ROLE_SUPPORT')) {
            $voucher = $voucherRepository->createByUser($user);

            if ($voucher instanceof Voucher) {
                $request->getSession()->getFlashBag()->add('success', 'flashes.voucher-creation-successful');
            }
        }
        return $this->redirect($this->generateUrl('index'));
    }

    /**
     * @param Request $request
     * @param User $user
     * @param PasswordChange $passwordChange
     */
    private function passwordChangeAction(Request $request, User $user, PasswordChange $passwordChange)
    {
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
