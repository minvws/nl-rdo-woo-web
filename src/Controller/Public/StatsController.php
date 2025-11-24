<?php

declare(strict_types=1);

namespace Shared\Controller\Public;

use Doctrine\ORM\EntityManagerInterface;
use Predis\Client;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Service\Search\Query\Definition\BrowseMainAggregationsQueryDefinition;
use Shared\Service\Search\SearchService;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 * @SuppressWarnings("PHPMD.LongVariable")
 */
class StatsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly Client $redis,
        private readonly SearchService $searchService,
        private readonly string $rabbitMqStatUrl,
        private readonly EntityStorageService $entityStorageService,
        private readonly ThumbnailStorageService $thumbnailStorageService,
        private readonly BrowseMainAggregationsQueryDefinition $aggregationsQueryDefinition,
    ) {
    }

    #[Route('/prometheus', name: 'app_prometheus', methods: ['GET'])]
    public function prometheus(): Response
    {
        $response = $this->render('public/stats/prometheus.txt.twig', [
            'app' => [
                'document_count' => $this->doctrine->getRepository(Document::class)->count([]),
                'dossier_count' => $this->doctrine->getRepository(AbstractDossier::class)->count([]),
                'page_count' => $this->doctrine->getRepository(Document::class)->pagecount(),
            ],
        ]);

        $response->headers->set('Content-Type', 'text/plain');

        return $response;
    }

    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $services = [
            'postgres' => $this->isPostgresAlive(),
            'redis' => $this->isRedisAlive(),
            'elastic' => $this->isElasticAlive(),
            'rabbitmq' => $this->isRabbitMqAlive(),
            'storage' => [
                'document' => $this->entityStorageService->isAlive(),
                'thumbnail' => $this->thumbnailStorageService->isAlive(),
            ],
        ];

        $statusCode = Response::HTTP_OK;
        foreach ($services as $status) {
            if ($status === false) {
                $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
            }
        }

        $healthy = $services['postgres']
            && $services['redis']
            && $services['elastic']
            && $services['rabbitmq']
            && $services['storage']['document']
            && $services['storage']['thumbnail']
        ;
        $response = new JsonResponse([
            'healthy' => $healthy,
            'externals' => $services,
        ], $statusCode);

        return $response->setPrivate();
    }

    protected function isRedisAlive(): bool
    {
        try {
            $this->redis->connect();
            $result = $this->redis->isConnected();
            if ($result !== true) {
                return false;
            }

            $result = $this->redis->ping('ping');

            return $result === 'ping';
        } catch (\Throwable) {
            // ignore
        }

        return false;
    }

    protected function isPostgresAlive(): bool
    {
        try {
            $result = $this->doctrine->getConnection()->fetchOne('SELECT 1');

            return $result === 1;
        } catch (\Throwable) {
            // ignore
        }

        return false;
    }

    protected function isElasticAlive(): bool
    {
        try {
            $result = $this->searchService->getResult(
                $this->aggregationsQueryDefinition,
            );

            return $result->hasFailed() === false;
        } catch (\Throwable) {
            // ignore
        }

        return false;
    }

    protected function isRabbitMqAlive(): bool
    {
        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => $this->rabbitMqStatUrl,
                'timeout' => 2.0,
                'connect_timeout' => 2.0,
            ]);
            $response = $client->get('/api/overview');

            return $response->getStatusCode() === Response::HTTP_OK;
        } catch (\Exception) {
            // ignore
        }

        return false;
    }
}
