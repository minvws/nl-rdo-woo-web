<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Domain\WooIndex\WooIndexSitemap;
use App\Domain\WooIndex\WooIndexSitemapService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

final class WooIndexController extends AbstractController
{
    public function __construct(private readonly WooIndexSitemapService $wooIndexSitemapService)
    {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/sitemap/woo-index/{id}/{file}',
        name: 'app_woo_index_sitemap_download',
        methods: ['GET'],
    )]
    public function download(
        #[MapEntity()] WooIndexSitemap $wooIndexSitemap,
        string $file,
    ): StreamedResponse {
        $stream = $this->wooIndexSitemapService->getFileAsStream($wooIndexSitemap, $file);

        return new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, Response::HTTP_OK, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
