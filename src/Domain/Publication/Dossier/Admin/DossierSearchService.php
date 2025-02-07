<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentRepository;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepository;
use App\Service\Security\Authorization\AuthorizationMatrix;

readonly class DossierSearchService
{
    public const SEARCH_RESULT_LIMIT = 4;

    public function __construct(
        private DossierRepository $dossierRepository,
        private DocumentRepository $documentRepository,
        private MainDocumentRepository $mainDocumentRepository,
        private AttachmentRepository $attachmentRepository,
        private AuthorizationMatrix $authorizationMatrix,
    ) {
    }

    /**
     * @return list<AbstractDossier>
     */
    public function searchDossiers(SearchParameters $searchParameters): array
    {
        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        return $this->dossierRepository->findBySearchTerm(
            searchTerm: $searchParameters->searchTerm,
            limit: self::SEARCH_RESULT_LIMIT,
            organisation: $organisation,
            dossierId: $searchParameters->dossierId,
            dossierType: $searchParameters->dossierType,
        );
    }

    /**
     * @return list<Document>
     */
    public function searchDocuments(SearchParameters $searchParameters): array
    {
        if ($searchParameters->shouldNotIncludeWooDecisionDocuments()) {
            return [];
        }

        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        return $this->documentRepository->findBySearchTerm(
            searchTerm: $searchParameters->searchTerm,
            limit: self::SEARCH_RESULT_LIMIT,
            organisation: $organisation,
            dossierId: $searchParameters->dossierId,
        );
    }

    /**
     * @return list<AbstractMainDocument>
     */
    public function searchMainDocuments(SearchParameters $searchParameters): array
    {
        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        return $this->mainDocumentRepository->findBySearchTerm(
            searchTerm: $searchParameters->searchTerm,
            limit: self::SEARCH_RESULT_LIMIT,
            organisation: $organisation,
            dossierId: $searchParameters->dossierId,
            dossierType: $searchParameters->dossierType,
        );
    }

    /**
     * @return list<AbstractAttachment>
     */
    public function searchAttachments(SearchParameters $searchParameters): array
    {
        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        return $this->attachmentRepository->findBySearchTerm(
            searchTerm: $searchParameters->searchTerm,
            limit: self::SEARCH_RESULT_LIMIT,
            organisation: $organisation,
            dossierId: $searchParameters->dossierId,
            dossierType: $searchParameters->dossierType,
        );
    }
}
