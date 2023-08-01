<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\WorkerStats;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatsController extends AbstractController
{
    protected EntityManagerInterface $doctrine;

    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;
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
}
