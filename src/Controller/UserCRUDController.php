<?php

declare(strict_types=1);

namespace App\Controller;

use App\Handler\DeleteHandler;
use App\Remover\VoucherRemover;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Traversable;

class UserCRUDController extends CRUDController
{
    public function __construct(private readonly DeleteHandler $deleteHandler, private readonly VoucherRemover $voucherRemover)
    {
    }

    public function batchActionRemoveVouchers(ProxyQueryInterface $query): RedirectResponse
    {
        $this->admin->checkAccess('edit');

        /** @phpstan-var Traversable $users */
        $users = $query->execute();

        $this->voucherRemover->removeUnredeemedVouchersByUsers(iterator_to_array($users, false));

        $this->addFlash(
            'sonata_flash_success',
            $this->trans('flash_batch_remove_vouchers_success')
        );

        return $this->redirectToList();
    }

    public function deleteAction(Request $request): Response
    {
        $object = $this->assertObjectExists($request, true);
        \assert(null !== $object);

        $this->admin->checkAccess('delete', $object);

        $preResponse = $this->preDelete($request, $object);
        if (null !== $preResponse) {
            return $preResponse;
        }

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
