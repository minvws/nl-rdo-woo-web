<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\ComplaintJudgement;

use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementDocument;
use App\Service\DownloadResponseHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DownloadController extends AbstractController
{
    public function __construct(
        private readonly DownloadResponseHelper $downloadHelper,
    ) {
    }

    #[Route(
        path: '/balie/dossier/complaint-judgement/{prefix}/{dossierId}/document/download',
        name: 'app_admin_complaintjudgement_document_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function downloadDocument(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        ComplaintJudgement $dossier,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId)')]
        ComplaintJudgementDocument $document,
    ): StreamedResponse {
        unset($dossier); // Only used for isGranted check

        return $this->downloadHelper->getResponseForEntityWithFileInfo($document);
    }
}
