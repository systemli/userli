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
    public function batchActionRemoveVouchers(ProxyQueryInterface $selectedModelQuery)
    {
        $this->admin->checkAccess('edit');

        $users = $selectedModelQuery->execute();

        $this->get(VoucherRemover::class)->removeUnredeemedVouchersByUsers($users);

        $this->addFlash(
            'sonata_flash_success',
            $this->trans('flash_batch_remove_vouchers_success')
        );

        return $this->redirectToList();
    }

    public function deleteAction($id)
    {
        $request = $this->getRequest();
        $id = $request->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id: %s', $id));
        }

        $this->admin->delete($object);

        $this->addFlash(
            'sonata_flash_success',
            $this->trans('flash_delete_success', [], 'SonataAdminBundle')
        );

        return $this->redirectToList();
    }

    public function batchActionDelete(ProxyQueryInterface $query)
    {
        $this->admin->checkAccess('batchDelete');

        $users = $query->execute();

        foreach ($users as $user) {
            $this->admin->delete($user);
        }

        $this->addFlash(
            'sonata_flash_success',
            $this->trans('flash_batch_delete_success', [], 'SonataAdminBundle')
        );

        return $this->redirectToList();
    }
}
