<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\MtaStsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MtaStsController extends AbstractController
{
    public function __construct(
        private readonly MtaStsService $mtaStsService,
    ) {
    }

    #[Route(
        path: '/.well-known/mta-sts.txt',
        name: 'mta_sts_policy',
        methods: ['GET'],
        stateless: true,
    )]
    public function policy(Request $request): Response
    {
        $policy = $this->mtaStsService->getPolicy($request->getHost());

        if (null === $policy) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new Response($policy, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
