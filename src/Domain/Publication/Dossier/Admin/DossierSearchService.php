<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Entity\Document;
use App\Repository\DocumentRepository;
use App\Service\Security\Authorization\AuthorizationMatrix;

readonly class DossierSearchService
{
    private const SEARCH_RESULT_LIMIT = 4;

    public function __construct(
        private AbstractDossierRepository $dossierRepository,
        private DocumentRepository $documentRepository,
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
            organisation: $organisation
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
            organisation: $organisation
        );
    }
}
