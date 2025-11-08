<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class ErrorController extends AbstractController
{
    public function show(FlattenException $exception, ?DebugLoggerInterface $logger = null): Response
    {
        return $this->render('Exception/show.html.twig', [
            'message' => $exception->getMessage(),
            'status_code' => $exception->getStatusCode(),
        ]);
    }
}
