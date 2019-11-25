<?php

namespace App\Controller;

use App\Remover\VoucherRemover;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class UserCRUDController extends CRUDController
{
    /**
     * @param Request $request
     *
     * @return RedirectResponse
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function batchActionRemoveVouchers(ProxyQueryInterface $selectedModelQuery, Request $request = null)
    {
        $this->admin->checkAccess('edit');

        $users = $selectedModelQuery->execute();

        $this->get(VoucherRemover::class)->removeUnredeemedVouchersByUsers($users);

        $this->addFlash(
            'sonata_flash_success',
            $this->admin->trans('flash_batch_remove_vouchers_success')
        );

        return new RedirectResponse(
            $this->admin->generateUrl('list', ['filter' => $this->admin->getFilterParameters()])
        );
    }
}
