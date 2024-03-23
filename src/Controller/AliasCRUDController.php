<?php

namespace App\Controller;

use App\Handler\DeleteHandler;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AliasCRUDController extends CRUDController
{
    public function __construct(private readonly DeleteHandler $deleteHandler)
    {
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

        $this->deleteHandler->deleteAlias($object);

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

        $aliases = $query->execute();

        foreach ($aliases as $alias) {
            $this->deleteHandler->deleteAlias($alias);
        }

        $this->addFlash(
            'sonata_flash_success',
            $this->trans('flash_batch_delete_success', [], 'SonataAdminBundle')
        );

        return $this->redirectToList();
    }
}
