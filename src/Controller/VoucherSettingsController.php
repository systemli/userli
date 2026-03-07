<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Voucher;
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

final class VoucherSettingsController extends AbstractController
{
    public function __construct(
        private readonly VoucherManager $manager,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/settings/vouchers/', name: 'settings_voucher_index', methods: ['GET'])]
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

        return $this->render('Settings/Voucher/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search, $domain, $status),
            'search' => $search,
            'status' => $status,
            'selectedDomain' => $domainId,
            'selectedDomainName' => $selectedDomainName,
        ]);
    }

    #[Route('/settings/vouchers/create', name: 'settings_voucher_create', methods: ['GET'])]
    public function create(): Response
    {
        $model = new VoucherModel();
        $model->setCode(RandomStringGenerator::generate(6, true));

        $user = $this->getUser();
        if ($user instanceof User) {
            $model->setDomain($user->getDomain());
        }

        $form = $this->createForm(VoucherType::class, $model, [
            'action' => $this->generateUrl('settings_voucher_create_post'),
            'method' => 'POST',
        ]);

        return $this->render('Settings/Voucher/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/vouchers/create', name: 'settings_voucher_create_post', methods: ['POST'])]
    public function createSubmit(Request $request): Response
    {
        $model = new VoucherModel();
        $form = $this->createForm(VoucherType::class, $model);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->manager->createForAdmin($model->getCode(), $model->getUser(), $model->getDomain());
                $this->addFlash('success', 'settings.voucher.create.success');

                return $this->redirectToRoute('settings_voucher_index');
            } catch (ValidationException) {
                $this->addFlash('error', 'settings.voucher.create.error');
            }
        }

        return $this->render('Settings/Voucher/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/vouchers/delete/{id}', name: 'settings_voucher_delete', methods: ['POST'])]
    public function delete(#[MapEntity] Voucher $voucher, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_voucher_'.$voucher->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('settings_voucher_index');
        }

        $this->manager->delete($voucher);
        $this->addFlash('success', 'settings.voucher.delete.success');

        return $this->redirectToRoute('settings_voucher_index');
    }
}
