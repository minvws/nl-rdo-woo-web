<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\DecisionAttachment;
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
        path: '/balie/dossier/woodecision/{prefix}/{dossierId}/decision-attachment/{attachmentId}/download',
        name: 'app_admin_woodecision_decisionattachment_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function downloadDecisionAttachment(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        WooDecision $dossier,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId, attachmentId)')]
        DecisionAttachment $decisionAttachment,
    ): StreamedResponse {
        unset($dossier); // Only used for isGranted check

        return $this->downloadHelper->getResponseForEntityWithFileInfo($decisionAttachment);
    }
}
