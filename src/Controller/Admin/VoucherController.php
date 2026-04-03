<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Exception\ValidationException;
use App\Form\Model\VoucherModel;
use App\Form\VoucherType;
use App\Helper\RandomStringGenerator;
use App\Service\VoucherManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VoucherController extends AbstractController
{
    public function __construct(
        private readonly VoucherManager $manager,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/admin/vouchers/', name: 'admin_voucher_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');
        $status = $request->query->getString('status', '');
        $domainId = $request->query->getInt('domain', 0);

        $domain = null;
        $selectedDomainName = '';
        if ($domainId > 0) {
            $domain = $this->em->getRepository(Domain::class)->find($domainId);
            if ($domain instanceof Domain) {
                $selectedDomainName = $domain->getName() ?? '';
            }
        }

        $invitationDisabled = false;
        if (!$this->isGranted(Roles::ADMIN)) {
            $user = $this->getUser();
            if ($user instanceof User) {
                $userDomain = $user->getDomain();
                $invitationDisabled = null === $userDomain || !$userDomain->isInvitationEnabled();
            }
        }

        return $this->render('Admin/Voucher/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search, $domain, $status),
            'search' => $search,
            'status' => $status,
            'selectedDomain' => $domainId,
            'selectedDomainName' => $selectedDomainName,
            'invitationDisabled' => $invitationDisabled,
        ]);
    }

    #[Route('/admin/vouchers/create', name: 'admin_voucher_create', methods: ['GET'])]
    public function create(): Response
    {
        if ($this->isInvitationDisabledForCurrentUser()) {
            return $this->redirectToRoute('admin_voucher_index');
        }

        $isAdmin = $this->isGranted(Roles::ADMIN);
        $model = new VoucherModel();
        $model->setCode(RandomStringGenerator::generate(6, true));

        $user = $this->getUser();
        if ($user instanceof User) {
            $model->setDomain($user->getDomain());
        }

        $form = $this->createForm(VoucherType::class, $model, [
            'action' => $this->generateUrl('admin_voucher_create_post'),
            'method' => 'POST',
            'is_admin' => $isAdmin,
        ]);

        return $this->render('Admin/Voucher/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/vouchers/create', name: 'admin_voucher_create_post', methods: ['POST'])]
    public function createSubmit(Request $request): Response
    {
        if ($this->isInvitationDisabledForCurrentUser()) {
            return $this->redirectToRoute('admin_voucher_index');
        }

        $isAdmin = $this->isGranted(Roles::ADMIN);
        $model = new VoucherModel();

        if (!$isAdmin) {
            $user = $this->getUser();
            if ($user instanceof User) {
                $model->setDomain($user->getDomain());
            }
        }

        $form = $this->createForm(VoucherType::class, $model, [
            'is_admin' => $isAdmin,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('create', $model);

            try {
                $this->manager->createForAdmin($model->getCode(), $model->getUser(), $model->getDomain());
                $this->addFlash('success', 'admin.voucher.create.success');

                return $this->redirectToRoute('admin_voucher_index');
            } catch (ValidationException) {
                $this->addFlash('error', 'admin.voucher.create.error');
            }
        }

        return $this->render('Admin/Voucher/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/vouchers/delete/{id}', name: 'admin_voucher_delete', methods: ['POST'])]
    public function delete(#[MapEntity] Voucher $voucher, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('view', $voucher);

        if (!$this->isCsrfTokenValid('delete_voucher_'.$voucher->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_voucher_index');
        }

        $this->manager->delete($voucher);
        $this->addFlash('success', 'admin.voucher.delete.success');

        return $this->redirectToRoute('admin_voucher_index');
    }

    private function isInvitationDisabledForCurrentUser(): bool
    {
        if ($this->isGranted(Roles::ADMIN)) {
            return false;
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return true;
        }

        $domain = $user->getDomain();

        return null === $domain || !$domain->isInvitationEnabled();
    }
}
