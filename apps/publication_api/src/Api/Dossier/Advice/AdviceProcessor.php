<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException as ApiPlatformValidationException;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use PublicationApi\Api\Dossier\DossierNumberValidator;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Api\Dossier\ExternalIdInUseException;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicMapper;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicService;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use PublicationApi\FeatureFlag\DossierUpdateGuard;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Document\DocumentPrefixDeterminer;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function sprintf;

/**
 * @implements ProcessorInterface<AdviceRequestDto,?AdviceResponseDto>
 */
final readonly class AdviceProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNumberValidator $dossierNumberValidator,
        private DossierSupportService $dossierSupportService,
        private DossierAttachmentValidator $dossierAttachmentValidator,
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private DossierUpdateGuard $dossierUpdateGuard,
        private DossierRepository $dossierRepository,
        private DossierValidator $dossierValidator,
        private AdviceMapper $adviceMapper,
        private DocumentPrefixDeterminer $documentPrefixDeterminer,
        private AttachmentSynchronizer $attachmentSynchronizer,
        private OrganisationResolver $organisationResolver,
        private NoticeNotPublicService $noticeNotPublicService,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?AdviceResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, AdviceRequestDto::class);
        Assert::string($uriVariables['dossierExternalId']);
        $dossierExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $dossier = $this->dossierRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);

        if ($dossier !== null && ! $dossier instanceof Advice) {
            throw ExternalIdInUseException::forExternalIdAlreadyUsed($dossier->getType());
        }

        if ($dossier === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNumberValidator->validate($data->dossierNumber, $documentPrefix);
            $dossier = $this->create($organisation, $department, $subject, $data, $dossierExternalId, $documentPrefix);

            return $this->adviceMapper->fromEntity($dossier);
        }

        $this->dossierUpdateGuard->assertDossierIsEditable($dossier);

        $this->dossierNumberValidator->validate($data->dossierNumber, $dossier->getDocumentPrefix(), $dossier->getId());
        $this->update($dossier, $organisation, $department, $subject, $data);

        return $this->adviceMapper->fromEntity($dossier);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        AdviceRequestDto $adviceRequestDto,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): Advice {
        $advice = AdviceMapper::create(
            $adviceRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        if ($adviceRequestDto->mainDocument !== null) {
            $mainDocument = AdviceMainDocumentMapper::create($advice, $adviceRequestDto->mainDocument);
            $advice->setMainDocument($mainDocument);
            $this->dossierMainDocumentValidator->validate($mainDocument);
        } else {
            $noticeNotPublic = $adviceRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublic);

            $advice->setNoticeNotPublic(
                NoticeNotPublicMapper::create($advice, $noticeNotPublic),
            );
        }

        $this->dossierAttachmentValidator->assertUniqueExternalIds($adviceRequestDto->attachments);
        $attachments = $this->getAttachments($advice, $adviceRequestDto->attachments);
        $this->validateAdviceAttachments($attachments, $advice->getStatus());
        $this->dossierSupportService->addAttachments($advice, $attachments);

        $this->dossierValidator->validateDossier($advice);
        $this->dossierSupportService->autoPublish($advice);
        $this->dossierSupportService->validateCompletionAndPersist($advice);
        $this->dossierSupportService->synchronizeArtifacts($advice);

        return $advice;
    }

    private function update(
        Advice $advice,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        AdviceRequestDto $adviceRequestDto,
    ): void {
        $advice = AdviceMapper::update($advice, $adviceRequestDto, $organisation, $department, $subject);

        if ($adviceRequestDto->mainDocument !== null) {
            if ($advice->getNoticeNotPublic() !== null) {
                $this->noticeNotPublicService->deleteFromDossier($advice);
            }

            $mainDocument = $advice->getMainDocument() !== null
                ? AdviceMainDocumentMapper::update($advice, $adviceRequestDto->mainDocument)
                : AdviceMainDocumentMapper::create($advice, $adviceRequestDto->mainDocument);
            $advice->setMainDocument($mainDocument);
            $this->dossierMainDocumentValidator->validate($mainDocument);
        } else {
            if ($advice->getMainDocument() !== null) {
                $this->messageBus->dispatch(new DeleteMainDocumentCommand($advice->getId()));
            }

            $noticeNotPublicDto = $adviceRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublicDto);

            $notice = $advice->getNoticeNotPublic() !== null
                ? $this->noticeNotPublicService->updateForDossier($advice, $noticeNotPublicDto)
                : $this->noticeNotPublicService->createForDossier($advice, $noticeNotPublicDto);
            $advice->setNoticeNotPublic($notice);
        }

        $this->dossierAttachmentValidator->assertUniqueExternalIds($adviceRequestDto->attachments);
        $this->dossierAttachmentValidator->assertNoAttachmentRemovalInNonConcept($advice, $adviceRequestDto->attachments);
        $attachments = $this->getAttachments($advice, $adviceRequestDto->attachments);
        $this->validateAdviceAttachments($attachments, $advice->getStatus());
        $this->attachmentSynchronizer->sync($advice, $adviceRequestDto->attachments);

        $this->dossierValidator->validateDossier($advice);
        $this->dossierSupportService->autoPublish($advice);
        $this->dossierSupportService->validateCompletionAndPersist($advice);
        $this->dossierSupportService->synchronizeArtifacts($advice);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<AdviceAttachment>
     */
    private function getAttachments(Advice $advice, array $attachments): array
    {
        return array_values(array_map(static fn (AttachmentRequestDto $attachment): AdviceAttachment => AdviceAttachmentMapper::create(
            $advice,
            $attachment,
        ), $attachments));
    }

    /**
     * @param list<AdviceAttachment> $attachments
     */
    private function validateAdviceAttachments(array $attachments, DossierStatus $dossierStatus): void
    {
        $attachmentType = AttachmentType::REQUEST_FOR_ADVICE;
        if ($this->hasMoreThanOneAttachmentOfType($attachments, $attachmentType)) {
            throw new ApiPlatformValidationException(ConstraintViolationList::createFromMessage(sprintf(
                'dossier should have at most one attachment of type "%s"',
                $attachmentType->value,
            )));
        }

        $this->dossierAttachmentValidator->validate($attachments, $dossierStatus);
    }

    /**
     * @param list<AdviceAttachment> $attachments
     */
    private function hasMoreThanOneAttachmentOfType(array $attachments, AttachmentType $attachmentType): bool
    {
        return count(array_filter($attachments, static fn (AdviceAttachment $attachment): bool => $attachment->getType() === $attachmentType)) > 1;
    }
}
