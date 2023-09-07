<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\WorkerStats;
use App\Service\Search\Model\Config;
use App\Service\Search\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly Client $redis,
        private readonly SearchService $searchService,
        private readonly string $rabbitMqStatUrl
    ) {
    }

    #[Route('/prometheus', name: 'app_prometheus', methods: ['GET'])]
    public function prometheus(): Response
    {
        $response = $this->render('stats/prometheus.txt.twig', [
            'app' => [
                'document_count' => $this->doctrine->getRepository(Document::class)->count([]),
                'dossier_count' => $this->doctrine->getRepository(Dossier::class)->count([]),
                'page_count' => $this->doctrine->getRepository(Document::class)->pagecount(),
            ],
            'worker' => [
                'pdf' => [
                    'create_temp_dir' => $this->doctrine->getRepository(WorkerStats::class)->getDuration('pdf.create_temp_dir'),
                    'cat_page_from_pdf' => $this->doctrine->getRepository(WorkerStats::class)->getDuration('pdf.cat_page_from_pdf'),
                    'run_tika' => $this->doctrine->getRepository(WorkerStats::class)->getDuration('pdf.run_tika'),
                    'page_to_png' => $this->doctrine->getRepository(WorkerStats::class)->getDuration('pdf.page_to_png'),
                    'tesseract' => $this->doctrine->getRepository(WorkerStats::class)->getDuration('pdf.tesseract'),
                    'delete_temp_dir' => $this->doctrine->getRepository(WorkerStats::class)->getDuration('pdf.delete_temp_dir'),
                    'index_page' => $this->doctrine->getRepository(WorkerStats::class)->getDuration('pdf.index_page'),
                ],
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
        ];

        $statusCode = Response::HTTP_OK;
        foreach ($services as $status) {
            if ($status === false) {
                $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
            }
        }

        $healthy = $services['postgres'] && $services['redis'] && $services['elastic'] && $services['rabbitmq'];
        $response = new JsonResponse([
            'healthy' => $healthy,
            'externals' => [
                'postgres' => $services['postgres'],
                'redis' => $services['redis'],
                'elastic' => $services['elastic'],
                'rabbitmq' => $services['rabbitmq'],
            ],
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
            $result = $this->searchService->search(new Config());

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
