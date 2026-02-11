<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Validator\Exception\ValidationException;
use Override;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierProcessor;
use PublicationApi\Api\Publication\Dossier\WooDecision\Document\WooDecisionDocumentMapper;
use PublicationApi\Api\Publication\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
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
use Shared\Service\Inventory\DocumentUpdater;
use Shared\Service\MainDocumentService;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Webmozart\Assert\Assert;

use function array_map;
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
    ) {
        parent::__construct(
            $attachmentService,
            $departmentRepository,
            $dossierDispatcher,
            $dossierService,
            $mainDocumentService,
            $organisationRepository,
            $subjectRepository,
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

        $organisation = $this->getOrganisation($uriVariables);
        $subject = $this->getSubject($data, $organisation);
        $department = $this->getDepartment($organisation, $data->departmentId);
        $wooDecision = $this->wooDecisionRepository->findByOrganisationAndExternalId($organisation, $wooDecisionExternalId);

        if ($wooDecision === null) {
            $wooDecision = $this->create($organisation, $department, $subject, $data, $wooDecisionExternalId);

            return WooDecisionMapper::fromEntity($wooDecision);
        }

        $this->update($wooDecision, $organisation, $department, $subject, $data);

        return WooDecisionMapper::fromEntity($wooDecision);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        WooDecisionRequestDto $wooDecisionRequestDto,
        string $wooDecisionExternalId,
    ): WooDecision {
        $wooDecision = WooDecisionMapper::create($wooDecisionRequestDto, $organisation, $department, $subject, $wooDecisionExternalId);
        $mainDocument = WooDecisionMainDocumentMapper::create($wooDecision, $wooDecisionRequestDto->mainDocument);
        $attachments = $this->getAttachments($wooDecision, $wooDecisionRequestDto->attachments);
        $documents = $this->getDocuments($wooDecision, $wooDecisionRequestDto->documents);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);
        $this->documentService->validateDocuments($documents);

        $wooDecision->setMainDocument($mainDocument);
        $this->addAttachments($wooDecision, $attachments);
        $this->addDossierDocuments($wooDecision, $documents);

        $this->validateDossier($wooDecision);
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
        $mainDocument = WooDecisionMainDocumentMapper::update($wooDecision, $wooDecisionRequestDto->mainDocument);
        $attachments = $this->getAttachments($wooDecision, $wooDecisionRequestDto->attachments);
        $documents = $this->getDocuments($wooDecision, $wooDecisionRequestDto->documents);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);
        $this->validateDocuments($documents);

        $wooDecision->setMainDocument($mainDocument);
        $this->removeDossierAttachments($wooDecision);
        $this->addAttachments($wooDecision, $attachments);

        $this->removeDossierDocuments($wooDecision);
        $this->addDossierDocuments($wooDecision, $documents);

        $this->validateDossier($wooDecision);

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
            $document = $this->documentRepository->findByExternalId(ExternalId::create($wooDecisionDocumentRequestDto->externalId));

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

            $document = $this->documentRepository->findByExternalId(ExternalId::create($wooDecisionDocumentRequestDto->externalId));
            if ($document === null) {
                continue;
            }

            $refersTo = array_map(ExternalId::create(...), $refersTo);

            $this->documentUpdater->updateDocumentReferralsByDocumentExternalId($document, $refersTo);
        }
    }
}
