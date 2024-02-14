<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;

class DiwooController extends AbstractController
{
    public function __construct(private readonly DocumentRepository $documentRepository)
    {
    }

    #[Cache(maxage: 3600, public: true, mustRevalidate: true)]
    #[Route('/sitemap-diwoo-infocat014.xml', name: 'diwoo_sitemap', methods: ['GET'])]
    public function sitemap(): Response
    {
        // hardcoded for now, will need pagination on 50.000 in future
        $documents = $this->documentRepository->getPublishedDocuments(1000);
        $urls = [];
        foreach ($documents as $document) {
            $urls[] = [
                'document' => $document,
                'lastmod' => $document->getUpdatedAt()->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.5',
            ];
        }

        $response = new Response(
            $this->renderView('diwoo/sitemap.xml.twig', ['urls' => $urls]),
            200
        );
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }

    #[Cache(maxage: 3600, public: true, mustRevalidate: true)]
    #[Route('/sitemapindex-diwoo-infocat014.xml', name: 'diwoo_sitemapindex', methods: ['GET'])]
    public function index(): Response
    {
        $response = new Response(
            $this->renderView('diwoo/sitemapindex.xml.twig'),
            200
        );
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }
}
