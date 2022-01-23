<?php

namespace App\Controller;

use Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseExceptionController;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * Class ExceptionController.
 */
class ExceptionController extends BaseExceptionController
{
    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null): Response
    {
        $code = $exception->getStatusCode();

        return new Response(
            $this->twig->render(
                'Exception/show.html.twig',
                ['message' => $exception->getMessage(), 'status_code' => $code]
            ),
            $code
        );
    }
}
