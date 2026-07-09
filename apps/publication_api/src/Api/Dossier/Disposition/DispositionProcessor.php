<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

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
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements ProcessorInterface<DispositionRequestDto,?DispositionResponseDto>
 */
final readonly class DispositionProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNumberValidator $dossierNumberValidator,
        private DossierSupportService $dossierSupportService,
        private DossierUpdateGuard $dossierUpdateGuard,
        private DossierRepository $dossierRepository,
        private DispositionMapper $dispositionMapper,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?DispositionResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, DispositionRequestDto::class);
        Assert::string($uriVariables['dossierExternalId']);
        $dispositionExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $dossier = $this->dossierRepository->findByOrganisationAndExternalId($organisation, $dispositionExternalId);

        if ($dossier !== null && ! $dossier instanceof Disposition) {
            throw ExternalIdInUseException::forExternalIdAlreadyUsed($dossier->getType());
        }

        if ($dossier === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNumberValidator->validate($data->dossierNumber, $documentPrefix);
            $dossier = $this->create($organisation, $department, $subject, $data, $dispositionExternalId, $documentPrefix);

            return $this->dispositionMapper->fromEntity($dossier);
        }

        $this->dossierUpdateGuard->assertDossierIsEditable($dossier);

        $this->dossierNumberValidator->validate($data->dossierNumber, $dossier->getDocumentPrefix(), $dossier->getId());
        $this->update($dossier, $organisation, $department, $subject, $data);

        return $this->dispositionMapper->fromEntity($dossier);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        DispositionRequestDto $dispositionRequestDto,
        ExternalId $dispositionExternalId,
        string $documentPrefix,
    ): Disposition {
        $disposition = DispositionMapper::create(
            $dispositionRequestDto,
            $organisation,
            $department,
            $subject,
            $dispositionExternalId,
            $documentPrefix,
        );

        if ($dispositionRequestDto->mainDocument !== null) {
            $mainDocument = DispositionMainDocumentMapper::create($disposition, $dispositionRequestDto->mainDocument);
            $disposition->setMainDocument($mainDocument);
            $this->dossierSupportService->validateMainDocument($mainDocument);
        } else {
            $noticeNotPublic = $dispositionRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublic);

            $disposition->setNoticeNotPublic(
                NoticeNotPublicMapper::create($disposition, $noticeNotPublic),
            );
        }

        $attachments = $this->getAttachments($disposition, $dispositionRequestDto->attachments);
        $this->dossierSupportService->validateAttachments($attachments);
        $this->dossierSupportService->addAttachments($disposition, $attachments);

        $this->dossierSupportService->validateDossier($disposition);
        $this->dossierSupportService->dispatchCreateDossierCommand($disposition);

        return $disposition;
    }

    private function update(
        Disposition $disposition,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        DispositionRequestDto $dispositionRequestDto,
    ): void {
        $disposition = DispositionMapper::update($disposition, $dispositionRequestDto, $organisation, $department, $subject);

        if ($dispositionRequestDto->mainDocument !== null) {
            if ($disposition->getNoticeNotPublic() !== null) {
                $this->noticeNotPublicService->deleteFromDossier($disposition);
            }

            $mainDocument = $disposition->getMainDocument() !== null
                ? DispositionMainDocumentMapper::update($disposition, $dispositionRequestDto->mainDocument)
                : DispositionMainDocumentMapper::create($disposition, $dispositionRequestDto->mainDocument);
            $disposition->setMainDocument($mainDocument);
            $this->dossierSupportService->validateMainDocument($mainDocument);
        } else {
            if ($disposition->getMainDocument() !== null) {
                $this->messageBus->dispatch(new DeleteMainDocumentCommand($disposition->getId()));
            }

            $noticeNotPublicDto = $dispositionRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublicDto);

            $notice = $disposition->getNoticeNotPublic() !== null
                ? $this->noticeNotPublicService->updateForDossier($disposition, $noticeNotPublicDto)
                : $this->noticeNotPublicService->createForDossier($disposition, $noticeNotPublicDto);
            $disposition->setNoticeNotPublic($notice);
        }

        $attachments = $this->getAttachments($disposition, $dispositionRequestDto->attachments);
        $this->dossierSupportService->validateAttachments($attachments);
        $this->attachmentSynchronizer->sync($disposition, $dispositionRequestDto->attachments);

        $this->dossierSupportService->validateDossier($disposition);
        $this->dossierSupportService->dispatchUpdateDossierCommand($disposition);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<DispositionAttachment>
     */
    private function getAttachments(Disposition $disposition, array $attachments): array
    {
        return array_values(array_map(static fn (AttachmentRequestDto $attachment): DispositionAttachment => DispositionAttachmentMapper::create(
            $disposition,
            $attachment,
        ), $attachments));
    }
}
