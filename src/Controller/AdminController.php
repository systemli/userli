<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends AbstractController
{
    /**
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->render('Admin/index.html.twig');
    }
}
