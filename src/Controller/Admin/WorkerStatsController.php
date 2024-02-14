<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\WorkerStats;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class WorkerStatsController extends AbstractController
{
    protected EntityManagerInterface $doctrine;

    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/balie/workerstats', name: 'app_admin_worker_stats', methods: ['GET'])]
    #[IsGranted('AuthMatrix.stat.read')]
    public function stats(): Response
    {
        $entries = $this->doctrine->getRepository(WorkerStats::class)->findAll();

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
