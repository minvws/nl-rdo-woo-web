<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

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
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicMapper;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicService;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use PublicationApi\FeatureFlag\DossierUpdateGuard;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Document\DocumentPrefixDeterminer;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements ProcessorInterface<CovenantRequestDto,?CovenantResponseDto>
 */
final readonly class CovenantProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNumberValidator $dossierNumberValidator,
        private DossierSupportService $dossierSupportService,
        private DossierUpdateGuard $dossierUpdateGuard,
        private DossierRepository $dossierRepository,
        private DossierValidator $dossierValidator,
        private CovenantMapper $covenantMapper,
        private DocumentPrefixDeterminer $documentPrefixDeterminer,
        private AttachmentSynchronizer $attachmentSynchronizer,
        private DossierAttachmentValidator $dossierAttachmentValidator,
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private OrganisationResolver $organisationResolver,
        private NoticeNotPublicService $noticeNotPublicService,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?CovenantResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, CovenantRequestDto::class);
        Assert::string($uriVariables['dossierExternalId']);
        $covenantExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $dossier = $this->dossierRepository->findByOrganisationAndExternalId($organisation, $covenantExternalId);

        if ($dossier !== null && ! $dossier instanceof Covenant) {
            throw ExternalIdInUseException::forExternalIdAlreadyUsed($dossier->getType());
        }

        if ($dossier === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNumberValidator->validate($data->dossierNumber, $documentPrefix);
            $dossier = $this->create($organisation, $department, $subject, $data, $covenantExternalId, $documentPrefix);

            return $this->covenantMapper->fromEntity($dossier);
        }

        $this->dossierUpdateGuard->assertDossierIsEditable($dossier);

        $this->dossierNumberValidator->validate($data->dossierNumber, $dossier->getDocumentPrefix(), $dossier->getId());
        $this->update($dossier, $organisation, $department, $subject, $data);

        return $this->covenantMapper->fromEntity($dossier);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        CovenantRequestDto $covenantRequestDto,
        ExternalId $covenantExternalId,
        string $documentPrefix,
    ): Covenant {
        $covenant = CovenantMapper::create(
            $covenantRequestDto,
            $organisation,
            $department,
            $subject,
            $covenantExternalId,
            $documentPrefix,
        );

        if ($covenantRequestDto->mainDocument !== null) {
            $mainDocument = CovenantMainDocumentMapper::create($covenant, $covenantRequestDto->mainDocument);
            $covenant->setMainDocument($mainDocument);
            $this->dossierMainDocumentValidator->validate($mainDocument);
        } else {
            $noticeNotPublic = $covenantRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublic);

            $covenant->setNoticeNotPublic(
                NoticeNotPublicMapper::create($covenant, $noticeNotPublic),
            );
        }

        $this->dossierAttachmentValidator->assertUniqueExternalIds($covenantRequestDto->attachments);
        $attachments = $this->getAttachments($covenant, $covenantRequestDto->attachments);
        $this->dossierAttachmentValidator->validate($attachments, $covenant->getStatus());
        $this->dossierSupportService->addAttachments($covenant, $attachments);

        $this->dossierValidator->validateDossier($covenant);
        $this->dossierSupportService->autoPublish($covenant);
        $this->dossierSupportService->validateCompletionAndPersist($covenant);
        $this->dossierSupportService->synchronizeArtifacts($covenant);

        return $covenant;
    }

    private function update(
        Covenant $covenant,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        CovenantRequestDto $covenantRequestDto,
    ): void {
        $covenant = CovenantMapper::update($covenant, $covenantRequestDto, $organisation, $department, $subject);

        if ($covenantRequestDto->mainDocument !== null) {
            if ($covenant->getNoticeNotPublic() !== null) {
                $this->noticeNotPublicService->deleteFromDossier($covenant);
            }

            $mainDocument = $covenant->getMainDocument() !== null
                ? CovenantMainDocumentMapper::update($covenant, $covenantRequestDto->mainDocument)
                : CovenantMainDocumentMapper::create($covenant, $covenantRequestDto->mainDocument);
            $covenant->setMainDocument($mainDocument);
            $this->dossierMainDocumentValidator->validate($mainDocument);
        } else {
            if ($covenant->getMainDocument() !== null) {
                $this->messageBus->dispatch(new DeleteMainDocumentCommand($covenant->getId()));
            }

            $noticeNotPublicDto = $covenantRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublicDto);

            $notice = $covenant->getNoticeNotPublic() !== null
                ? $this->noticeNotPublicService->updateForDossier($covenant, $noticeNotPublicDto)
                : $this->noticeNotPublicService->createForDossier($covenant, $noticeNotPublicDto);
            $covenant->setNoticeNotPublic($notice);
        }

        $this->dossierAttachmentValidator->assertUniqueExternalIds($covenantRequestDto->attachments);
        $this->dossierAttachmentValidator->assertNoAttachmentRemovalInNonConcept($covenant, $covenantRequestDto->attachments);
        $attachments = $this->getAttachments($covenant, $covenantRequestDto->attachments);
        $this->dossierAttachmentValidator->validate($attachments, $covenant->getStatus());
        $this->attachmentSynchronizer->sync($covenant, $covenantRequestDto->attachments);

        $this->dossierValidator->validateDossier($covenant);
        $this->dossierSupportService->autoPublish($covenant);
        $this->dossierSupportService->validateCompletionAndPersist($covenant);
        $this->dossierSupportService->synchronizeArtifacts($covenant);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<CovenantAttachment>
     */
    private function getAttachments(Covenant $covenant, array $attachments): array
    {
        return array_values(array_map(static fn (AttachmentRequestDto $attachment): CovenantAttachment => CovenantAttachmentMapper::create(
            $covenant,
            $attachment,
        ), $attachments));
    }
}
