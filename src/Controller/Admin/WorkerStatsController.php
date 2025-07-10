<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Stats\WorkerStatsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class WorkerStatsController extends AbstractController
{
    public function __construct(
        private readonly WorkerStatsRepository $repository,
    ) {
    }

    #[Route('/balie/workerstats', name: 'app_admin_worker_stats', methods: ['GET'])]
    #[IsGranted('AuthMatrix.stat.read')]
    public function stats(): Response
    {
        $entries = $this->repository->findAll();

        $data = [];
        foreach ($entries as $entry) {
            $data[] = [
                'section' => $entry->getSection(),
                'created_at' => $entry->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'duration' => $entry->getDuration(),
            ];
        }

        return $this->render('admin/stats/workers.html.twig', [
            'data' => $data,
        ]);
    }
}
