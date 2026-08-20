<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use PublicationApi\Api\Dossier\DossierNumberValidator;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Api\Dossier\ExternalIdInUseException;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use PublicationApi\FeatureFlag\DossierUpdateGuard;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Document\DocumentPrefixDeterminer;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachment;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements ProcessorInterface<DraftDecisionRequestDto,?DraftDecisionResponseDto>
 */
final readonly class DraftDecisionProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNumberValidator $dossierNumberValidator,
        private DossierSupportService $dossierSupportService,
        private DossierUpdateGuard $dossierUpdateGuard,
        private DossierRepository $dossierRepository,
        private DraftDecisionMapper $draftDecisionMapper,
        private DocumentPrefixDeterminer $documentPrefixDeterminer,
        private AttachmentSynchronizer $attachmentSynchronizer,
        private OrganisationResolver $organisationResolver,
        private DossierValidator $dossierValidator,
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private DossierAttachmentValidator $dossierAttachmentValidator,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?DraftDecisionResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, DraftDecisionRequestDto::class);
        Assert::string($uriVariables['dossierExternalId']);

        $draftDecisionExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $dossier = $this->dossierRepository->findByOrganisationAndExternalId($organisation, $draftDecisionExternalId);

        if ($dossier !== null && ! $dossier instanceof DraftDecision) {
            throw ExternalIdInUseException::forExternalIdAlreadyUsed($dossier->getType());
        }

        if ($dossier === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNumberValidator->validate($data->dossierNumber, $documentPrefix);
            $dossier = $this->create($organisation, $department, $subject, $data, $draftDecisionExternalId, $documentPrefix);

            return $this->draftDecisionMapper->fromEntity($dossier);
        }

        $this->dossierUpdateGuard->assertDossierIsEditable($dossier);

        $this->dossierNumberValidator->validate($data->dossierNumber, $dossier->getDocumentPrefix(), $dossier->getId());
        $this->update($dossier, $organisation, $department, $subject, $data);

        return $this->draftDecisionMapper->fromEntity($dossier);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        DraftDecisionRequestDto $draftDecisionRequestDto,
        ExternalId $draftDecisionExternalId,
        string $documentPrefix,
    ): DraftDecision {
        $draftDecision = DraftDecisionMapper::create(
            $draftDecisionRequestDto,
            $organisation,
            $department,
            $subject,
            $draftDecisionExternalId,
            $documentPrefix,
        );

        $mainDocumentDto = $draftDecisionRequestDto->mainDocument;
        Assert::notNull($mainDocumentDto);

        $mainDocument = DraftDecisionMainDocumentMapper::create($draftDecision, $mainDocumentDto);
        $draftDecision->setMainDocument($mainDocument);
        $this->dossierMainDocumentValidator->validate($mainDocument);

        $attachmentRequestDtos = $this->getAttachmentRequestDtos($draftDecisionRequestDto);
        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);
        $attachments = $this->getAttachments($draftDecision, $attachmentRequestDtos);
        $this->dossierAttachmentValidator->validate($attachments, $draftDecision->getStatus());
        $this->dossierSupportService->addAttachments($draftDecision, $attachments);

        $this->dossierValidator->validateDossier($draftDecision);
        $this->dossierSupportService->autoPublish($draftDecision);
        $this->dossierSupportService->validateCompletionAndPersist($draftDecision);
        $this->dossierSupportService->synchronizeArtifacts($draftDecision);

        return $draftDecision;
    }

    private function update(
        DraftDecision $draftDecision,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        DraftDecisionRequestDto $draftDecisionRequestDto,
    ): void {
        $draftDecision = DraftDecisionMapper::update($draftDecision, $draftDecisionRequestDto, $organisation, $department, $subject);

        $mainDocumentDto = $draftDecisionRequestDto->mainDocument;
        Assert::notNull($mainDocumentDto);

        $mainDocument = $draftDecision->getMainDocument() !== null
            ? DraftDecisionMainDocumentMapper::update($draftDecision, $mainDocumentDto)
            : DraftDecisionMainDocumentMapper::create($draftDecision, $mainDocumentDto);
        $draftDecision->setMainDocument($mainDocument);
        $this->dossierMainDocumentValidator->validate($mainDocument);

        $attachmentRequestDtos = $this->getAttachmentRequestDtos($draftDecisionRequestDto);
        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);
        $this->dossierAttachmentValidator->assertNoAttachmentRemovalInNonConcept($draftDecision, $attachmentRequestDtos);
        $attachments = $this->getAttachments($draftDecision, $attachmentRequestDtos);
        $this->dossierAttachmentValidator->validate($attachments, $draftDecision->getStatus());
        $this->attachmentSynchronizer->sync($draftDecision, $attachmentRequestDtos);

        $this->dossierValidator->validateDossier($draftDecision);
        $this->dossierSupportService->autoPublish($draftDecision);
        $this->dossierSupportService->validateCompletionAndPersist($draftDecision);
        $this->dossierSupportService->synchronizeArtifacts($draftDecision);
    }

    /**
     * @return list<AttachmentRequestDto>
     */
    private function getAttachmentRequestDtos(DraftDecisionRequestDto $draftDecisionRequestDto): array
    {
        return array_values(array_map(
            static fn (DraftDecisionAttachmentRequestDto $attachment): AttachmentRequestDto => $attachment->toAttachmentRequestDto(),
            $draftDecisionRequestDto->attachments,
        ));
    }

    /**
     * @param list<AttachmentRequestDto> $attachments
     *
     * @return list<DraftDecisionAttachment>
     */
    private function getAttachments(DraftDecision $draftDecision, array $attachments): array
    {
        return array_values(array_map(static function (AttachmentRequestDto $attachment) use ($draftDecision): DraftDecisionAttachment {
            return DraftDecisionAttachmentMapper::create(
                $draftDecision,
                $attachment,
            );
        }, $attachments));
    }
}
