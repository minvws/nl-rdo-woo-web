<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class StatsController extends AbstractController
{
    protected EntityManagerInterface $doctrine;

    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/balie/stats', name: 'app_admin_stats', methods: ['GET'])]
    #[IsGranted('AuthMatrix.stat.read')]
    public function stats(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addItem('admin.global.statistics');

        $rabbitmqStats = null;

        try {
            $client = new Client([
                'base_uri' => $this->getParameter('rabbitmq_stats_url'),
                'timeout' => 2.0,
            ]);
            $response = $client->get('/api/queues');
            $rabbitmqStats = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception) {
            // ignore
        }

        return $this->render('admin/stats/index.html.twig', [
            'document_count' => $this->doctrine->getRepository(Document::class)->count([]),
            'dossier_count' => $this->doctrine->getRepository(AbstractDossier::class)->count([]),
            'page_count' => $this->doctrine->getRepository(Document::class)->pagecount(),
            'rabbitmq_stats' => $rabbitmqStats,
        ]);
    }
}
