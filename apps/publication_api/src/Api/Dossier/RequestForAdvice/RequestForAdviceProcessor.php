<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

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
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachment;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements ProcessorInterface<RequestForAdviceRequestDto,?RequestForAdviceResponseDto>
 */
final readonly class RequestForAdviceProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNumberValidator $dossierNumberValidator,
        private DossierSupportService $dossierSupportService,
        private DossierAttachmentValidator $dossierAttachmentValidator,
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private DossierUpdateGuard $dossierUpdateGuard,
        private DossierRepository $dossierRepository,
        private DossierValidator $dossierValidator,
        private RequestForAdviceMapper $requestForAdviceMapper,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?RequestForAdviceResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, RequestForAdviceRequestDto::class);
        Assert::string($uriVariables['dossierExternalId']);

        $requestForAdviceExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $dossier = $this->dossierRepository->findByOrganisationAndExternalId($organisation, $requestForAdviceExternalId);

        if ($dossier !== null && ! $dossier instanceof RequestForAdvice) {
            throw ExternalIdInUseException::forExternalIdAlreadyUsed($dossier->getType());
        }

        if ($dossier === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNumberValidator->validate($data->dossierNumber, $documentPrefix);
            $dossier = $this->create($organisation, $department, $subject, $data, $requestForAdviceExternalId, $documentPrefix);

            return $this->requestForAdviceMapper->fromEntity($dossier);
        }

        $this->dossierUpdateGuard->assertDossierIsEditable($dossier);

        $this->dossierNumberValidator->validate($data->dossierNumber, $dossier->getDocumentPrefix(), $dossier->getId());
        $this->update($dossier, $organisation, $department, $subject, $data);

        return $this->requestForAdviceMapper->fromEntity($dossier);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        RequestForAdviceRequestDto $requestForAdviceRequestDto,
        ExternalId $requestForAdviceExternalId,
        string $documentPrefix,
    ): RequestForAdvice {
        $requestForAdvice = RequestForAdviceMapper::create(
            $requestForAdviceRequestDto,
            $organisation,
            $department,
            $subject,
            $requestForAdviceExternalId,
            $documentPrefix,
        );

        if ($requestForAdviceRequestDto->mainDocument !== null) {
            $mainDocument = RequestForAdviceMainDocumentMapper::create($requestForAdvice, $requestForAdviceRequestDto->mainDocument);
            $requestForAdvice->setMainDocument($mainDocument);
            $this->dossierMainDocumentValidator->validate($mainDocument);
        } else {
            $noticeNotPublic = $requestForAdviceRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublic);

            $requestForAdvice->setNoticeNotPublic(
                NoticeNotPublicMapper::create($requestForAdvice, $noticeNotPublic),
            );
        }

        $this->dossierAttachmentValidator->assertUniqueExternalIds($requestForAdviceRequestDto->attachments);
        $attachments = $this->getAttachments($requestForAdvice, $requestForAdviceRequestDto->attachments);
        $this->dossierAttachmentValidator->validate($attachments, $requestForAdvice->getStatus());
        $this->dossierSupportService->addAttachments($requestForAdvice, $attachments);

        $this->dossierValidator->validateDossier($requestForAdvice);
        $this->dossierSupportService->autoPublish($requestForAdvice);
        $this->dossierSupportService->validateCompletionAndPersist($requestForAdvice);
        $this->dossierSupportService->synchronizeArtifacts($requestForAdvice);

        return $requestForAdvice;
    }

    private function update(
        RequestForAdvice $requestForAdvice,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        RequestForAdviceRequestDto $requestForAdviceRequestDto,
    ): void {
        $requestForAdvice = RequestForAdviceMapper::update($requestForAdvice, $requestForAdviceRequestDto, $organisation, $department, $subject);

        if ($requestForAdviceRequestDto->mainDocument !== null) {
            if ($requestForAdvice->getNoticeNotPublic() !== null) {
                $this->noticeNotPublicService->deleteFromDossier($requestForAdvice);
            }

            $mainDocument = $requestForAdvice->getMainDocument() !== null
                ? RequestForAdviceMainDocumentMapper::update($requestForAdvice, $requestForAdviceRequestDto->mainDocument)
                : RequestForAdviceMainDocumentMapper::create($requestForAdvice, $requestForAdviceRequestDto->mainDocument);
            $requestForAdvice->setMainDocument($mainDocument);
            $this->dossierMainDocumentValidator->validate($mainDocument);
        } else {
            if ($requestForAdvice->getMainDocument() !== null) {
                $this->messageBus->dispatch(new DeleteMainDocumentCommand($requestForAdvice->getId()));
            }

            $noticeNotPublicDto = $requestForAdviceRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublicDto);

            $notice = $requestForAdvice->getNoticeNotPublic() !== null
                ? $this->noticeNotPublicService->updateForDossier($requestForAdvice, $noticeNotPublicDto)
                : $this->noticeNotPublicService->createForDossier($requestForAdvice, $noticeNotPublicDto);
            $requestForAdvice->setNoticeNotPublic($notice);
        }

        $this->dossierAttachmentValidator->assertUniqueExternalIds($requestForAdviceRequestDto->attachments);
        $this->dossierAttachmentValidator->assertNoAttachmentRemovalInNonConcept($requestForAdvice, $requestForAdviceRequestDto->attachments);
        $attachments = $this->getAttachments($requestForAdvice, $requestForAdviceRequestDto->attachments);
        $this->dossierAttachmentValidator->validate($attachments, $requestForAdvice->getStatus());
        $this->attachmentSynchronizer->sync($requestForAdvice, $requestForAdviceRequestDto->attachments);

        $this->dossierValidator->validateDossier($requestForAdvice);
        $this->dossierSupportService->autoPublish($requestForAdvice);
        $this->dossierSupportService->validateCompletionAndPersist($requestForAdvice);
        $this->dossierSupportService->synchronizeArtifacts($requestForAdvice);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<RequestForAdviceAttachment>
     */
    private function getAttachments(RequestForAdvice $requestForAdvice, array $attachments): array
    {
        return array_values(array_map(static function (AttachmentRequestDto $attachment) use ($requestForAdvice): RequestForAdviceAttachment {
            return RequestForAdviceAttachmentMapper::create(
                $requestForAdvice,
                $attachment,
            );
        }, $attachments));
    }
}
