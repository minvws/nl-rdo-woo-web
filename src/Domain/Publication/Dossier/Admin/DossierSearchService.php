<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentRepository;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepository;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Component\Uid\Uuid;

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
     * @return AbstractDossier[]
     */
    public function searchDossiers(
        string $searchTerm,
        ?Uuid $dossierId = null,
        ?DossierType $dossierType = null,
    ): array {
        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        return $this->dossierRepository->findBySearchTerm(
            searchTerm: $searchTerm,
            limit: self::SEARCH_RESULT_LIMIT,
            organisation: $organisation,
            dossierId: $dossierId,
            dossierType: $dossierType,
        );
    }

    /**
     * @return Document[]
     */
    public function searchDocuments(
        string $searchTerm,
        ?Uuid $dossierId = null,
    ): array {
        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        return $this->documentRepository->findBySearchTerm(
            searchTerm: $searchTerm,
            limit: self::SEARCH_RESULT_LIMIT,
            organisation: $organisation,
            dossierId: $dossierId,
        );
    }

    /**
     * @return list<AbstractMainDocument>
     */
    public function searchMainDocuments(
        string $searchTerm,
        ?Uuid $dossierId = null,
        ?DossierType $dossierType = null,
    ): array {
        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        return $this->mainDocumentRepository->findBySearchTerm(
            searchTerm: $searchTerm,
            limit: self::SEARCH_RESULT_LIMIT,
            organisation: $organisation,
            dossierId: $dossierId,
            dossierType: $dossierType,
        );
    }

    /**
     * @return list<AbstractAttachment>
     */
    public function searchAttachments(
        string $searchTerm,
        ?Uuid $dossierId = null,
        ?DossierType $dossierType = null,
    ): array {
        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        return $this->attachmentRepository->findBySearchTerm(
            searchTerm: $searchTerm,
            limit: self::SEARCH_RESULT_LIMIT,
            organisation: $organisation,
            dossierId: $dossierId,
            dossierType: $dossierType,
        );
    }
}
