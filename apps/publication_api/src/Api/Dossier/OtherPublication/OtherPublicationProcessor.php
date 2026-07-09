<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierNumberValidator;
use PublicationApi\Api\Dossier\DossierSupportService;
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
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements ProcessorInterface<OtherPublicationRequestDto,?OtherPublicationResponseDto>
 */
final readonly class OtherPublicationProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNumberValidator $dossierNumberValidator,
        private DossierSupportService $dossierSupportService,
        private DossierUpdateGuard $dossierUpdateGuard,
        private DossierRepository $dossierRepository,
        private OtherPublicationMapper $otherPublicationMapper,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?OtherPublicationResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, OtherPublicationRequestDto::class);
        Assert::string($uriVariables['dossierExternalId']);
        $otherPublicationExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $dossier = $this->dossierRepository->findByOrganisationAndExternalId($organisation, $otherPublicationExternalId);

        if ($dossier !== null && ! $dossier instanceof OtherPublication) {
            throw ExternalIdInUseException::forExternalIdAlreadyUsed($dossier->getType());
        }

        if ($dossier === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNumberValidator->validate($data->dossierNumber, $documentPrefix);
            $dossier = $this->create($organisation, $department, $subject, $data, $otherPublicationExternalId, $documentPrefix);

            return $this->otherPublicationMapper->fromEntity($dossier);
        }

        $this->dossierUpdateGuard->assertDossierIsEditable($dossier);

        $this->dossierNumberValidator->validate($data->dossierNumber, $dossier->getDocumentPrefix(), $dossier->getId());
        $this->update($dossier, $organisation, $department, $subject, $data);

        return $this->otherPublicationMapper->fromEntity($dossier);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        OtherPublicationRequestDto $otherPublicationRequestDto,
        ExternalId $otherPublicationExternalId,
        string $documentPrefix,
    ): OtherPublication {
        $otherPublication = OtherPublicationMapper::create(
            $otherPublicationRequestDto,
            $organisation,
            $department,
            $subject,
            $otherPublicationExternalId,
            $documentPrefix,
        );

        if ($otherPublicationRequestDto->mainDocument !== null) {
            $mainDocument = OtherPublicationMainDocumentMapper::create($otherPublication, $otherPublicationRequestDto->mainDocument);
            $otherPublication->setMainDocument($mainDocument);
            $this->dossierSupportService->validateMainDocument($mainDocument);
        } else {
            $noticeNotPublic = $otherPublicationRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublic);

            $otherPublication->setNoticeNotPublic(
                NoticeNotPublicMapper::create($otherPublication, $noticeNotPublic),
            );
        }

        $attachments = $this->getAttachments($otherPublication, $otherPublicationRequestDto->attachments);
        $this->dossierSupportService->validateAttachments($attachments);
        $this->dossierSupportService->addAttachments($otherPublication, $attachments);

        $this->dossierSupportService->validateDossier($otherPublication);
        $this->dossierSupportService->dispatchCreateDossierCommand($otherPublication);

        return $otherPublication;
    }

    private function update(
        OtherPublication $otherPublication,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        OtherPublicationRequestDto $otherPublicationRequestDto,
    ): void {
        $otherPublication = OtherPublicationMapper::update($otherPublication, $otherPublicationRequestDto, $organisation, $department, $subject);

        if ($otherPublicationRequestDto->mainDocument !== null) {
            if ($otherPublication->getNoticeNotPublic() !== null) {
                $this->noticeNotPublicService->deleteFromDossier($otherPublication);
            }

            $mainDocument = $otherPublication->getMainDocument() !== null
                ? OtherPublicationMainDocumentMapper::update($otherPublication, $otherPublicationRequestDto->mainDocument)
                : OtherPublicationMainDocumentMapper::create($otherPublication, $otherPublicationRequestDto->mainDocument);
            $otherPublication->setMainDocument($mainDocument);
            $this->dossierSupportService->validateMainDocument($mainDocument);
        } else {
            if ($otherPublication->getMainDocument() !== null) {
                $this->messageBus->dispatch(new DeleteMainDocumentCommand($otherPublication->getId()));
            }

            $noticeNotPublicDto = $otherPublicationRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublicDto);

            $notice = $otherPublication->getNoticeNotPublic() !== null
                ? $this->noticeNotPublicService->updateForDossier($otherPublication, $noticeNotPublicDto)
                : $this->noticeNotPublicService->createForDossier($otherPublication, $noticeNotPublicDto);
            $otherPublication->setNoticeNotPublic($notice);
        }

        $attachments = $this->getAttachments($otherPublication, $otherPublicationRequestDto->attachments);
        $this->dossierSupportService->validateAttachments($attachments);
        $this->attachmentSynchronizer->sync($otherPublication, $otherPublicationRequestDto->attachments);

        $this->dossierSupportService->validateDossier($otherPublication);
        $this->dossierSupportService->dispatchUpdateDossierCommand($otherPublication);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<OtherPublicationAttachment>
     */
    private function getAttachments(OtherPublication $otherPublication, array $attachments): array
    {
        return array_values(array_map(static function (AttachmentRequestDto $attachment) use ($otherPublication): OtherPublicationAttachment {
            return OtherPublicationAttachmentMapper::create(
                $otherPublication,
                $attachment,
            );
        }, $attachments));
    }
}
