<?php

namespace App\Controller;

use App\Handler\DeleteHandler;
use App\Remover\VoucherRemover;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class UserCRUDController extends CRUDController
{
    private DeleteHandler $deleteHandler;

    public function __construct(DeleteHandler $deleteHandler) {
        $this->deleteHandler = $deleteHandler;
    }

    /**
     * @param ProxyQueryInterface $query
     *
     * @return RedirectResponse
     */
    public function batchActionRemoveVouchers(ProxyQueryInterface $query): RedirectResponse
    {
        $this->admin->checkAccess('edit');

        $users = $query->execute();

        $this->get(VoucherRemover::class)->removeUnredeemedVouchersByUsers(iterator_to_array($users, false));

        $this->addFlash(
            'sonata_flash_success',
            $this->trans('flash_batch_remove_vouchers_success')
        );

        return $this->redirectToList();
    }

    /**
     * @param int|string|null $id
     *
     * @return RedirectResponse
     */
    public function deleteAction($id): RedirectResponse
    {
        $request = $this->getRequest();
        $this->assertObjectExists($request, true);

        $id = $request->get($this->admin->getIdParameter());
        \assert(null !== $id);
        $object = $this->admin->getObject($id);
        \assert(null !== $object);

        $this->admin->checkAccess('delete', $object);

        $objectName = $this->admin->toString($object);

        $this->deleteHandler->deleteUser($object);

        $this->addFlash(
            'sonata_flash_success',
            $this->trans(
                'flash_delete_success',
                ['%name%' => $this->escapeHtml($objectName)],
                'SonataAdminBundle'
            )
        );

        return $this->redirectToList();
    }

    /**
     * @param ProxyQueryInterface $query
     *
     * @return RedirectResponse
     */
    public function batchActionDelete(ProxyQueryInterface $query): RedirectResponse
    {
        $this->admin->checkAccess('batchDelete');

        $users = $query->execute();

        foreach ($users as $user) {
            $this->deleteHandler->deleteUser($user);
        }

        $this->addFlash(
            'sonata_flash_success',
            $this->trans('flash_batch_delete_success', [], 'SonataAdminBundle')
        );

        return $this->redirectToList();
    }
}
