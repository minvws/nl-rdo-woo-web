<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException as ApiPlatformValidationException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierNrValidator;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentMapper;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentValidator;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use PublicationApi\Domain\Inquiry\InquiryService;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Document\DocumentPrefixDeterminer;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\DocumentService;
use Shared\Service\Inquiry\DocumentInquiryNumbers;
use Shared\Service\Inquiry\InquiryChangeset;
use Shared\Service\Inquiry\InquiryNumbers;
use Shared\Service\Inventory\DocumentUpdater;
use Shared\Service\Inventory\InventoryUpdater;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Webmozart\Assert\Assert;

use function array_flip;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;

/**
 * @implements ProcessorInterface<WooDecisionRequestDto,?WooDecisionResponseDto>
 */
final readonly class WooDecisionProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNrValidator $dossierNrValidator,
        private DossierSupportService $dossierSupportService,
        private DocumentRepository $documentRepository,
        private DocumentService $documentService,
        private DocumentUpdater $documentUpdater,
        private WooDecisionDispatcher $wooDecisionDispatcher,
        private WooDecisionRepository $wooDecisionRepository,
        private InquiryService $inquiryService,
        private WooDecisionMapper $wooDecisionMapper,
        private DocumentPrefixDeterminer $documentPrefixDeterminer,
        private AttachmentSynchronizer $attachmentSynchronizer,
        private OrganisationResolver $organisationResolver,
        private WooDecisionDocumentMapper $wooDecisionDocumentMapper,
        private WooDecisionDocumentValidator $wooDecisionDocumentValidator,
        private InventoryUpdater $inventoryUpdater,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?WooDecisionResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, WooDecisionRequestDto::class);
        Assert::string($uriVariables['dossierExternalId']);

        $dossierExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $wooDecision = $this->wooDecisionRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);

        $this->wooDecisionDocumentValidator->validate($data->documents);

        if ($wooDecision === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNrValidator->validate($data->dossierNumber, $documentPrefix);
            $wooDecision = $this->create($organisation, $department, $subject, $data, $dossierExternalId, $documentPrefix);

            return $this->wooDecisionMapper->fromEntity($wooDecision);
        }

        $this->dossierNrValidator->validate($data->dossierNumber, $wooDecision->getDocumentPrefix(), $wooDecision->getId());
        $this->update($wooDecision, $organisation, $department, $subject, $data);

        return $this->wooDecisionMapper->fromEntity($wooDecision);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        WooDecisionRequestDto $wooDecisionRequestDto,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): WooDecision {
        $wooDecision = WooDecisionMapper::create($wooDecisionRequestDto, $organisation, $department, $subject, $dossierExternalId, $documentPrefix);
        $mainDocument = WooDecisionMainDocumentRequestMapper::create($wooDecision, $wooDecisionRequestDto->mainDocument);
        $attachments = $this->getAttachments($wooDecision, $wooDecisionRequestDto->attachments);
        $documents = $this->getDocuments($wooDecision, $wooDecisionRequestDto->documents);

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);
        $this->validateDocuments($documents);
        $wooDecision->setMainDocument($mainDocument);
        $this->dossierSupportService->addAttachments($wooDecision, $attachments);
        $this->addDossierDocuments($wooDecision, $documents);

        $this->dossierSupportService->validateDossier($wooDecision);

        $this->wooDecisionRepository->save($wooDecision, true);

        $this->handleInquiries($wooDecision, $wooDecisionRequestDto->documents);

        $this->dossierSupportService->dispatchCreateDossierCommand($wooDecision);
        $this->updateDocumentRefersTo($wooDecisionRequestDto->documents);
        $this->inventoryUpdater->updateWooDecisionInventories($wooDecision);

        return $wooDecision;
    }

    private function update(
        WooDecision $wooDecision,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        WooDecisionRequestDto $wooDecisionRequestDto,
    ): void {
        $wooDecision = WooDecisionMapper::update($wooDecision, $wooDecisionRequestDto, $organisation, $department, $subject);
        $mainDocument = WooDecisionMainDocumentRequestMapper::update($wooDecision, $wooDecisionRequestDto->mainDocument);
        $attachments = $this->getAttachments($wooDecision, $wooDecisionRequestDto->attachments);
        $documents = $this->getDocuments($wooDecision, $wooDecisionRequestDto->documents);

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);
        $this->validateDocuments($documents);

        $wooDecision->setMainDocument($mainDocument);
        $this->attachmentSynchronizer->sync($wooDecision, $wooDecisionRequestDto->attachments);

        $previousDocumentInquiryNumbers = $this->getDocumentInquiryNumbers($wooDecision);

        $this->removeDossierDocuments($wooDecision);
        $this->addDossierDocuments($wooDecision, $documents);

        $this->dossierSupportService->validateDossier($wooDecision);

        $this->wooDecisionRepository->save($wooDecision, true);

        $this->handleInquiries(
            $wooDecision,
            $wooDecisionRequestDto->documents,
            $previousDocumentInquiryNumbers,
        );

        $this->wooDecisionDispatcher->dispatchUpdateDecisionCommand($wooDecision);
        $this->dossierSupportService->dispatchUpdateDossierDetailsCommand($wooDecision);
        $this->updateDocumentRefersTo($wooDecisionRequestDto->documents);
        $this->inventoryUpdater->updateWooDecisionInventories($wooDecision);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<WooDecisionAttachment>
     */
    private function getAttachments(WooDecision $wooDecision, array $attachmentRequestDtos): array
    {
        return array_values(array_map(static fn (AttachmentRequestDto $attachment): WooDecisionAttachment => WooDecisionAttachmentMapper::create(
            $wooDecision,
            $attachment,
        ), $attachmentRequestDtos));
    }

    /**
     * @param list<Document> $documents
     */
    private function validateDocuments(array $documents): void
    {
        try {
            $this->documentService->validateDocuments($documents);
        } catch (ValidationFailedException $validationFailedException) {
            $violations = $this->dossierSupportService->prefixViolationsPropertyPath(
                $validationFailedException->getViolations(),
                'documents.',
            );
            throw new ApiPlatformValidationException($violations, previous: $validationFailedException);
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
            $document = $this->documentRepository->findByDossierAndExternalId($wooDecision, $wooDecisionDocumentRequestDto->externalId);

            if ($document instanceof Document) {
                return $this->wooDecisionDocumentMapper->update($wooDecision->getDocumentPrefix(), $document, $wooDecisionDocumentRequestDto);
            }

            return $this->wooDecisionDocumentMapper->create($wooDecision->getDocumentPrefix(), $wooDecisionDocumentRequestDto);
        }, $wooDecisionDocumentRequestDtos));
    }

    private function removeDossierDocuments(WooDecision $wooDecision): void
    {
        foreach ($wooDecision->getDocuments() as $document) {
            $wooDecision->removeDocument($document);
        }
    }

    /**
     * @param array<array-key,Document> $documents
     */
    private function addDossierDocuments(WooDecision $wooDecision, array $documents): void
    {
        foreach ($documents as $document) {
            $wooDecision->addDocument($document);
        }
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $wooDecisionDocumentRequestDtos
     */
    private function updateDocumentRefersTo(array $wooDecisionDocumentRequestDtos): void
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
     * @param list<WooDecisionDocumentRequestDto> $requestDocuments
     * @param Collection<string,DocumentInquiryNumbers> $previousDocumentInquiryNumbers
     */
    private function handleInquiries(
        WooDecision $wooDecision,
        array $requestDocuments,
        Collection $previousDocumentInquiryNumbers = new ArrayCollection(),
    ): void {
        $currentDocumentInquiryNumbers = $this->getDocumentInquiryNumbers($wooDecision);

        $allExternalIds = array_keys(array_flip(array_merge($previousDocumentInquiryNumbers->getKeys(), $currentDocumentInquiryNumbers->getKeys())));

        /** @var ArrayCollection<string,InquiryNumbers> $targetDocumentInquiryNumbers */
        $targetDocumentInquiryNumbers = new ArrayCollection($requestDocuments)
            ->reduce(static function (ArrayCollection $carry, WooDecisionDocumentRequestDto $documentRequestDto): ArrayCollection {
                $carry->set($documentRequestDto->externalId->toString(), new InquiryNumbers($documentRequestDto->inquiryNumbers));

                return $carry;
            }, new ArrayCollection());

        $inquiryChangeset = new InquiryChangeset($wooDecision->getOrganisation());
        foreach ($allExternalIds as $externalId) {
            $documentInquiryNumbers = $previousDocumentInquiryNumbers->get($externalId) ?? $currentDocumentInquiryNumbers->get($externalId);
            Assert::isInstanceOf($documentInquiryNumbers, DocumentInquiryNumbers::class);

            $inquiryChangeset
                ->updateInquiryNumbersForDocument(
                    $documentInquiryNumbers,
                    $targetDocumentInquiryNumbers->get($externalId) ?? InquiryNumbers::empty(),
                );
        }

        $this->inquiryService->applyChangesetSync($inquiryChangeset);
    }

    /**
     * @return Collection<string,DocumentInquiryNumbers>
     */
    private function getDocumentInquiryNumbers(WooDecision $wooDecision): Collection
    {
        /** @var Collection<string,DocumentInquiryNumbers> */
        return $wooDecision->getDocuments()
            ->reduce(static function (Collection $carry, Document $document): Collection {
                $externalId = $document->getExternalId()?->toString();

                if ($externalId === null) {
                    // A better solution for this issue should be implemented. See #6214:
                    throw new ApiPlatformValidationException(
                        // @phpcs:ignore Generic.Files.LineLength.TooLong
                        ConstraintViolationList::createFromMessage('Dossier has Document(s) without external ID(s). This is likely because this Dossier was updated through the UI.'),
                    );
                }

                $carry->set($externalId, DocumentInquiryNumbers::fromDocumentEntity($document));

                return $carry;
            }, new ArrayCollection());
    }
}
