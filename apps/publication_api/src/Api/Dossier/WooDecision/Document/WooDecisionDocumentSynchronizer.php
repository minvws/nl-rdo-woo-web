<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Document;

use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Dossier\DossierDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\ObsoleteFileRemover;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\DocumentUpdater;

use function array_map;
use function array_values;

readonly class WooDecisionDocumentSynchronizer
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentUpdater $documentUpdater,
        private ObsoleteFileRemover $obsoleteFileRemover,
        private DossierDocumentValidator $dossierDocumentValidator,
        private WooDecisionDocumentValidator $wooDecisionDocumentValidator,
    ) {
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $wooDecisionDocumentRequestDtos
     *
     * @throws ValidationException
     */
    public function validateRequest(array $wooDecisionDocumentRequestDtos): void
    {
        $this->wooDecisionDocumentValidator->validate($wooDecisionDocumentRequestDtos);
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $wooDecisionDocumentRequestDtos
     *
     * @throws ValidationException
     */
    public function create(WooDecision $wooDecision, array $wooDecisionDocumentRequestDtos): void
    {
        $documents = $this->getDocuments($wooDecision, $wooDecisionDocumentRequestDtos);
        $this->dossierDocumentValidator->validate($documents, $wooDecision->getStatus());
        $this->addDossierDocuments($wooDecision, $documents);
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $wooDecisionDocumentRequestDtos
     *
     * @throws ValidationException
     */
    public function update(WooDecision $wooDecision, array $wooDecisionDocumentRequestDtos): void
    {
        $this->dossierDocumentValidator->assertDocumentSetUnchangedInNonConcept($wooDecision, $wooDecisionDocumentRequestDtos);

        $documents = $this->getDocuments($wooDecision, $wooDecisionDocumentRequestDtos);
        $this->dossierDocumentValidator->validate($documents, $wooDecision->getStatus());
        $this->removeDossierDocuments($wooDecision);
        $this->addDossierDocuments($wooDecision, $documents);
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $wooDecisionDocumentRequestDtos
     */
    public function synchronizeRefersTo(array $wooDecisionDocumentRequestDtos): void
    {
        foreach ($wooDecisionDocumentRequestDtos as $wooDecisionDocumentRequestDto) {
            $refersTo = $wooDecisionDocumentRequestDto->refersTo;
            if ($refersTo === []) {
                continue;
            }

            $document = $this->documentRepository->findByExternalId($wooDecisionDocumentRequestDto->externalId);
            if ($document === null) {
                continue;
            }

            $this->documentUpdater->updateDocumentReferralsByDocumentExternalId($document, $refersTo);
        }
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $wooDecisionDocumentRequestDtos
     *
     * @return list<Document>
     */
    private function getDocuments(WooDecision $wooDecision, array $wooDecisionDocumentRequestDtos): array
    {
        return array_values(array_map(function (WooDecisionDocumentRequestDto $wooDecisionDocumentRequestDto) use ($wooDecision): Document {
            $existingDocument = $this->documentRepository->findByDossierAndExternalId($wooDecision, $wooDecisionDocumentRequestDto->externalId);

            $document = $existingDocument instanceof Document
                ? WooDecisionDocumentMapper::update($existingDocument, $wooDecisionDocumentRequestDto)
                : WooDecisionDocumentMapper::create($wooDecisionDocumentRequestDto);

            $this->obsoleteFileRemover->removeIfObsolete($document);

            return $document;
        }, $wooDecisionDocumentRequestDtos));
    }

    private function removeDossierDocuments(WooDecision $wooDecision): void
    {
        foreach ($wooDecision->getDocuments() as $document) {
            $wooDecision->removeDocument($document);
        }
    }

    /**
     * @param list<Document> $documents
     */
    private function addDossierDocuments(WooDecision $wooDecision, array $documents): void
    {
        foreach ($documents as $document) {
            $wooDecision->addDocument($document);
        }
    }
}
