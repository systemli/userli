<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\OpenPgpKeyCacheKey;
use App\Repository\OpenPgpKeyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class WkdController extends AbstractController
{
    public function __construct(
        private readonly OpenPgpKeyRepository $openPgpKeyRepository,
        private readonly CacheInterface $cache,
    ) {
    }

    #[Route(
        path: '/.well-known/openpgpkey/{domain}/hu/{hash}',
        name: 'wkd_lookup',
        methods: ['GET', 'HEAD'],
        stateless: true,
    )]
    public function lookup(string $domain, string $hash): Response
    {
        $cacheKey = OpenPgpKeyCacheKey::WKD_LOOKUP->key(strtolower($hash).'@'.strtolower($domain));

        /** @var ?string $keyData */
        $keyData = $this->cache->get($cacheKey, function (ItemInterface $item) use ($domain, $hash): ?string {
            $item->expiresAfter(OpenPgpKeyCacheKey::TTL);

            $openPgpKey = $this->openPgpKeyRepository->findByWkdHash($hash, $domain);

            return $openPgpKey?->toBinary();
        });

        if (null === $keyData) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new Response($keyData, Response::HTTP_OK, [
            'Content-Type' => 'application/octet-stream',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    #[Route(
        path: '/.well-known/openpgpkey/{domain}/policy',
        name: 'wkd_policy',
        methods: ['GET', 'HEAD'],
        stateless: true,
    )]
    public function policy(): Response
    {
        return new Response('', Response::HTTP_OK, [
            'Content-Type' => 'text/plain',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
