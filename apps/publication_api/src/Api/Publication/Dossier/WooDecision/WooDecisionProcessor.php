<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Validator\Exception\ValidationException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Override;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierProcessor;
use PublicationApi\Api\Publication\Dossier\WooDecision\Document\WooDecisionDocumentMapper;
use PublicationApi\Api\Publication\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use PublicationApi\Domain\Inquiry\InquiryService;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DocumentService;
use Shared\Service\DossierService;
use Shared\Service\Inquiry\CaseNumbers;
use Shared\Service\Inquiry\DocumentCaseNumbers;
use Shared\Service\Inquiry\InquiryChangeset;
use Shared\Service\Inventory\DocumentUpdater;
use Shared\Service\MainDocumentService;
use Shared\ValueObject\ExternalId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Webmozart\Assert\Assert;

use function array_flip;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;

final class WooDecisionProcessor extends AbstractDossierProcessor
{
    public function __construct(
        AttachmentService $attachmentService,
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentService $documentService,
        private readonly DocumentUpdater $documentUpdater,
        DepartmentRepository $departmentRepository,
        DossierService $dossierService,
        MainDocumentService $mainDocumentService,
        OrganisationRepository $organisationRepository,
        SubjectRepository $subjectRepository,
        private readonly DossierDispatcher $dossierDispatcher,
        private readonly WooDecisionDispatcher $wooDecisionDispatcher,
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly InquiryService $inquiryService,
        private readonly Security $security,
        private readonly WooDecisionMapper $wooDecisionMapper,
    ) {
        parent::__construct(
            $attachmentService,
            $departmentRepository,
            $dossierDispatcher,
            $dossierService,
            $mainDocumentService,
            $organisationRepository,
            $subjectRepository,
            $this->security,
        );
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?WooDecisionDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, WooDecisionRequestDto::class);

        $wooDecisionExternalId = $uriVariables['wooDecisionExternalId'];
        Assert::string($wooDecisionExternalId);
        $wooDecisionExternalId = ExternalId::create($wooDecisionExternalId);

        $organisation = $this->getOrganisation($uriVariables);
        $subject = $this->getSubject($data, $organisation);
        $department = $this->getDepartment($organisation, $data->departmentId);
        $wooDecision = $this->wooDecisionRepository->findByOrganisationAndExternalId($organisation, $wooDecisionExternalId);

        if ($wooDecision === null) {
            $wooDecision = $this->create($organisation, $department, $subject, $data, $wooDecisionExternalId);

            return $this->wooDecisionMapper->fromEntity($wooDecision);
        }

        $this->update($wooDecision, $organisation, $department, $subject, $data);

        return $this->wooDecisionMapper->fromEntity($wooDecision);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        WooDecisionRequestDto $wooDecisionRequestDto,
        ExternalId $wooDecisionExternalId,
    ): WooDecision {
        $wooDecision = WooDecisionMapper::create($wooDecisionRequestDto, $organisation, $department, $subject, $wooDecisionExternalId);
        $mainDocument = WooDecisionMainDocumentRequestMapper::create($wooDecision, $wooDecisionRequestDto->mainDocument);
        $attachments = $this->getAttachments($wooDecision, $wooDecisionRequestDto->attachments);
        $documents = $this->getDocuments($wooDecision, $wooDecisionRequestDto->documents);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);
        $this->validateDocuments($documents);

        $wooDecision->setMainDocument($mainDocument);
        $this->addAttachments($wooDecision, $attachments);
        $this->addDossierDocuments($wooDecision, $documents);

        $this->validateDossier($wooDecision);
        $this->wooDecisionRepository->save($wooDecision, true);

        $this->handleInquiries($wooDecision, $wooDecisionRequestDto->documents);

        $this->dispatchCreateDossierCommand($wooDecision);
        $this->updateDocumentRefersTo($wooDecisionRequestDto->documents);

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

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);
        $this->validateDocuments($documents);

        $wooDecision->setMainDocument($mainDocument);
        $this->removeDossierAttachments($wooDecision);
        $this->addAttachments($wooDecision, $attachments);

        $previousDocumentCaseNumbers = $this->getDocumentCaseNumbers($wooDecision);

        $this->removeDossierDocuments($wooDecision);
        $this->addDossierDocuments($wooDecision, $documents);

        $this->validateDossier($wooDecision);
        $this->wooDecisionRepository->save($wooDecision, true);

        $this->handleInquiries(
            $wooDecision,
            $wooDecisionRequestDto->documents,
            $previousDocumentCaseNumbers,
        );

        $this->wooDecisionDispatcher->dispatchUpdateDecisionCommand($wooDecision);
        $this->dispatchUpdateDossierCommand($wooDecision);
        $this->updateDocumentRefersTo($wooDecisionRequestDto->documents);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<WooDecisionAttachment>
     */
    private function getAttachments(WooDecision $wooDecision, array $attachmentRequestDtos): array
    {
        return array_values(array_map(fn (AttachmentRequestDto $attachment): WooDecisionAttachment => WooDecisionAttachmentMapper::create(
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
            $violations = $this->prefixViolationsPropertyPath(
                $validationFailedException->getViolations(),
                'documents.'
            );
            throw new ValidationException($violations, previous: $validationFailedException);
        }
    }

    /**
     * @param array<array-key,WooDecisionDocumentRequestDto> $wooDecisionDocumentRequestDtos
     *
     * @return list<Document>
     */
    private function getDocuments(WooDecision $wooDecision, array $wooDecisionDocumentRequestDtos): array
    {
        return array_values(array_map(function (WooDecisionDocumentRequestDto $wooDecisionDocumentRequestDto) use ($wooDecision): Document {
            $document = $this->documentRepository->findByExternalId($wooDecisionDocumentRequestDto->externalId);

            if ($document instanceof Document) {
                return WooDecisionDocumentMapper::update($document, $wooDecisionDocumentRequestDto);
            }

            return WooDecisionDocumentMapper::create($wooDecision->getDocumentPrefix(), $wooDecisionDocumentRequestDto);
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

    #[Override]
    protected function dispatchUpdateDossierCommand(AbstractDossier $dossier): void
    {
        $this->dossierDispatcher->dispatchUpdateDossierDetailsCommand($dossier);
    }

    /**
     * @param array<array-key,WooDecisionDocumentRequestDto> $wooDecisionDocumentRequestDtos
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

            $refersTo = array_map(ExternalId::create(...), $refersTo);

            $this->documentUpdater->updateDocumentReferralsByDocumentExternalId($document, $refersTo);
        }
    }

    /**
     * @param array<array-key,WooDecisionDocumentRequestDto> $requestDocuments
     * @param Collection<string,DocumentCaseNumbers> $previousDocumentCaseNumbers
     */
    private function handleInquiries(
        WooDecision $wooDecision,
        array $requestDocuments,
        Collection $previousDocumentCaseNumbers = new ArrayCollection(),
    ): void {
        $currentDocumentCaseNumbers = $this->getDocumentCaseNumbers($wooDecision);

        $allExternalIds = array_keys(array_flip(array_merge($previousDocumentCaseNumbers->getKeys(), $currentDocumentCaseNumbers->getKeys())));

        /** @var ArrayCollection<string,CaseNumbers> $targetDocumentCaseNumbers */
        $targetDocumentCaseNumbers = new ArrayCollection($requestDocuments)
            ->reduce(function (ArrayCollection $carry, WooDecisionDocumentRequestDto $documentRequestDto): ArrayCollection {
                $carry->set($documentRequestDto->externalId->__toString(), new CaseNumbers($documentRequestDto->caseNumbers));

                return $carry;
            }, new ArrayCollection());

        $inquiryChangeset = new InquiryChangeset($wooDecision->getOrganisation());
        foreach ($allExternalIds as $externalId) {
            $documentCaseNumbers = $previousDocumentCaseNumbers->get($externalId) ?? $currentDocumentCaseNumbers->get($externalId);
            Assert::isInstanceOf($documentCaseNumbers, DocumentCaseNumbers::class);

            $inquiryChangeset
                ->updateCaseNrsForDocument(
                    $documentCaseNumbers,
                    $targetDocumentCaseNumbers->get($externalId) ?? CaseNumbers::empty(),
                );
        }

        $this->inquiryService->applyChangesetSync($inquiryChangeset);
    }

    /**
     * @return Collection<string,DocumentCaseNumbers>
     */
    private function getDocumentCaseNumbers(WooDecision $wooDecision): Collection
    {
        /** @var Collection<string,DocumentCaseNumbers> */
        return $wooDecision->getDocuments()
            ->reduce(function (Collection $carry, Document $document): Collection {
                $externalId = $document->getExternalId()?->__toString();

                if ($externalId === null) {
                    // A better solution for this issue should be implemented. See #6214:
                    throw new ValidationException(
                        // @phpcs:ignore Generic.Files.LineLength.TooLong
                        ConstraintViolationList::createFromMessage('Dossier has Document(s) without external ID(s). This is likely because this Dossier was updated through the UI.'),
                    );
                }

                $carry->set($externalId, DocumentCaseNumbers::fromDocumentEntity($document));

                return $carry;
            }, new ArrayCollection());
    }
}
