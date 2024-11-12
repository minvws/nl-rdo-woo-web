<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AbstractAttachmentRepository;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use App\Entity\Document;
use App\Repository\DocumentRepository;
use App\Service\Security\Authorization\AuthorizationMatrix;

readonly class DossierSearchService
{
    public const SEARCH_RESULT_LIMIT = 4;

    public function __construct(
        private AbstractDossierRepository $dossierRepository,
        private DocumentRepository $documentRepository,
        private AbstractMainDocumentRepository $mainDocumentRepository,
        private AbstractAttachmentRepository $attachmentRepository,
        private AuthorizationMatrix $authorizationMatrix,
    ) {
    }

    /**
     * @return AbstractDossier[]
     */
    public function searchDossiers(string $searchTerm): array
    {
        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        return $this->dossierRepository->findBySearchTerm(
            searchTerm: $searchTerm,
            limit: self::SEARCH_RESULT_LIMIT,
            organisation: $organisation,
        );
    }

    /**
     * @return Document[]
     */
    public function searchDocuments(string $searchTerm): array
    {
        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        return $this->documentRepository->findBySearchTerm(
            searchTerm: $searchTerm,
            limit: self::SEARCH_RESULT_LIMIT,
            organisation: $organisation,
        );
    }

    /**
     * @return list<AbstractMainDocument>
     */
    public function searchMainDocuments(string $searchTerm): array
    {
        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        return $this->mainDocumentRepository->findBySearchTerm(
            searchTerm: $searchTerm,
            limit: self::SEARCH_RESULT_LIMIT,
            organisation: $organisation,
        );
    }

    /**
     * @return list<AbstractAttachment>
     */
    public function searchAttachments(string $searchTerm): array
    {
        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        return $this->attachmentRepository->findBySearchTerm(
            searchTerm: $searchTerm,
            limit: self::SEARCH_RESULT_LIMIT,
            organisation: $organisation,
        );
    }
}
