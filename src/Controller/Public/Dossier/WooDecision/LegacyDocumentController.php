<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Exception\ViewingNotAllowedException;
use App\Service\Security\DossierVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

// Redirects old dossier urls (without prefixes in the url) to the new urls
class LegacyDocumentController extends AbstractController
{
    #[Route('/dossier/{dossierId}/document/{documentId}', name: 'app_legacy_document_detail', methods: ['GET'], priority: -1)]
    public function detail(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] WooDecision $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
    ): RedirectResponse {
        $this->validateAccess($dossier, $document);

        return $this->redirectToRoute(
            'app_document_detail',
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'documentId' => $document->getDocumentNr(),
            ],
            301,
        );
    }

    private function validateAccess(WooDecision $dossier, Document $document): void
    {
        if (! $document->getDossiers()->contains($dossier)) {
            throw ViewingNotAllowedException::forDossierOrDocument();
        }

        $this->denyAccessUnlessGranted(DossierVoter::VIEW, $document);
    }
}
