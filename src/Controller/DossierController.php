<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\BatchDownload;
use App\Entity\Dossier;
use App\Message\GenerateArchiveMessage;
use App\Service\Search\Model\Config;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DossierController extends AbstractController
{
    protected EntityManagerInterface $doctrine;
    protected MessageBusInterface $messageBus;
    protected TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $doctrine, MessageBusInterface $messageBus, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;
        $this->messageBus = $messageBus;
        $this->translator = $translator;
    }

    #[Route('/dossiers', name: 'app_dossier_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_search', ['type' => Config::TYPE_DOSSIER]);
    }

    #[Route('/dossier/{dossierId}', name: 'app_dossier_detail', methods: ['GET'])]
    public function detail(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Breadcrumbs $breadcrumbs
    ): Response {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addItem('Dossier');

        if (! $dossier->isVisible()) {
            throw $this->createNotFoundException('Dossier not found');
        }

        return $this->render('dossier/details.html.twig', [
            'dossier' => $dossier,
        ]);
    }

    #[Route('/dossier/{dossierId}/batch', name: 'app_dossier_batch', methods: ['POST'])]
    public function createBatch(
        Request $request,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
    ): Response {
        if (! $dossier->isVisible()) {
            throw $this->createNotFoundException('Dossier not found');
        }

        $docs = $request->request->all()['doc'] ?? [];
        if (! is_array($docs)) {
            $docs = [$docs];
        }

        $documents = [];
        foreach ($docs as $documentNr) {
            foreach ($dossier->getDocuments() as $document) {
                if ($document->getDocumentNr() === $documentNr) {
                    $documents[] = $document->getDocumentNr();
                    break;
                }
            }
        }

        if (count($documents) === 0) {
            $this->addFlash('warning', $this->translator->trans('No documents selected'));

            return $this->redirectToRoute('app_dossier_detail', ['dossierId' => $dossier->getDossierNr()]);
        }

        $batch = new BatchDownload();
        $batch->setStatus(BatchDownload::STATUS_PENDING);
        $batch->setDossier($dossier);
        $batch->setDownloaded(0);
        $batch->setExpiration(new \DateTimeImmutable('+48 hours'));
        $batch->setDocuments($documents);

        $this->doctrine->persist($batch);
        $this->doctrine->flush();

        // Dispatch message to generate archive
        $this->messageBus->dispatch(new GenerateArchiveMessage($batch->getId()));

        return $this->redirectToRoute('app_dossier_batch_detail', [
            'dossierId' => $dossier->getDossierNr(),
            'batchId' => $batch->getId(),
        ]);
    }

    #[Route('/dossier/{dossierId}/batch/{batchId}', name: 'app_dossier_batch_detail', methods: ['GET'])]
    public function batch(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['batchId' => 'id'])] BatchDownload $batch,
        Breadcrumbs $breadcrumbs
    ): Response {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Dossier', 'app_dossier_detail', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addItem('Download');

        if (! $dossier->isVisible()) {
            throw $this->createNotFoundException('Dossier not found');
        }

        return $this->render('dossier/batch.html.twig', [
            'dossier' => $dossier,
            'batch' => $batch,
        ]);
    }
}
