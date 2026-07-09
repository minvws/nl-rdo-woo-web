<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

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
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements ProcessorInterface<InvestigationReportRequestDto,?InvestigationReportResponseDto>
 */
final readonly class InvestigationReportProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNumberValidator $dossierNumberValidator,
        private DossierSupportService $dossierSupportService,
        private DossierUpdateGuard $dossierUpdateGuard,
        private DossierRepository $dossierRepository,
        private InvestigationReportMapper $investigationReportMapper,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?InvestigationReportResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, InvestigationReportRequestDto::class);
        Assert::string($uriVariables['dossierExternalId']);
        $dossierExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $dossier = $this->dossierRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);

        if ($dossier !== null && ! $dossier instanceof InvestigationReport) {
            throw ExternalIdInUseException::forExternalIdAlreadyUsed($dossier->getType());
        }

        if ($dossier === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNumberValidator->validate($data->dossierNumber, $documentPrefix);
            $dossier = $this->create($organisation, $department, $subject, $data, $dossierExternalId, $documentPrefix);

            return $this->investigationReportMapper->fromEntity($dossier);
        }

        $this->dossierUpdateGuard->assertDossierIsEditable($dossier);

        $this->dossierNumberValidator->validate($data->dossierNumber, $dossier->getDocumentPrefix(), $dossier->getId());
        $this->update($dossier, $organisation, $department, $subject, $data);

        return $this->investigationReportMapper->fromEntity($dossier);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        InvestigationReportRequestDto $investigationReportRequestDto,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): InvestigationReport {
        $investigationReport = InvestigationReportMapper::create(
            $investigationReportRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        if ($investigationReportRequestDto->mainDocument !== null) {
            $mainDocument = InvestigationReportMainDocumentMapper::create($investigationReport, $investigationReportRequestDto->mainDocument);
            $investigationReport->setMainDocument($mainDocument);
            $this->dossierSupportService->validateMainDocument($mainDocument);
        } else {
            $noticeNotPublic = $investigationReportRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublic);

            $investigationReport->setNoticeNotPublic(
                NoticeNotPublicMapper::create($investigationReport, $noticeNotPublic),
            );
        }

        $attachments = $this->getAttachments($investigationReport, $investigationReportRequestDto->attachments);
        $this->dossierSupportService->validateAttachments($attachments);
        $this->dossierSupportService->addAttachments($investigationReport, $attachments);

        $this->dossierSupportService->validateDossier($investigationReport);
        $this->dossierSupportService->dispatchCreateDossierCommand($investigationReport);

        return $investigationReport;
    }

    private function update(
        InvestigationReport $investigationReport,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        InvestigationReportRequestDto $investigationReportRequestDto,
    ): void {
        $investigationReport = InvestigationReportMapper::update(
            $investigationReport,
            $investigationReportRequestDto,
            $organisation,
            $department,
            $subject,
        );

        if ($investigationReportRequestDto->mainDocument !== null) {
            if ($investigationReport->getNoticeNotPublic() !== null) {
                $this->noticeNotPublicService->deleteFromDossier($investigationReport);
            }

            $mainDocument = $investigationReport->getMainDocument() !== null
                ? InvestigationReportMainDocumentMapper::update($investigationReport, $investigationReportRequestDto->mainDocument)
                : InvestigationReportMainDocumentMapper::create($investigationReport, $investigationReportRequestDto->mainDocument);
            $investigationReport->setMainDocument($mainDocument);
            $this->dossierSupportService->validateMainDocument($mainDocument);
        } else {
            if ($investigationReport->getMainDocument() !== null) {
                $this->messageBus->dispatch(new DeleteMainDocumentCommand($investigationReport->getId()));
            }

            $noticeNotPublicDto = $investigationReportRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublicDto);

            $notice = $investigationReport->getNoticeNotPublic() !== null
                ? $this->noticeNotPublicService->updateForDossier($investigationReport, $noticeNotPublicDto)
                : $this->noticeNotPublicService->createForDossier($investigationReport, $noticeNotPublicDto);
            $investigationReport->setNoticeNotPublic($notice);
        }

        $attachments = $this->getAttachments($investigationReport, $investigationReportRequestDto->attachments);
        $this->dossierSupportService->validateAttachments($attachments);
        $this->attachmentSynchronizer->sync($investigationReport, $investigationReportRequestDto->attachments);

        $this->dossierSupportService->validateDossier($investigationReport);
        $this->dossierSupportService->dispatchUpdateDossierCommand($investigationReport);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<InvestigationReportAttachment>
     */
    private function getAttachments(InvestigationReport $investigationReport, array $attachments): array
    {
        return array_values(array_map(
            static fn (AttachmentRequestDto $attachment): InvestigationReportAttachment => InvestigationReportAttachmentMapper::create(
                $investigationReport,
                $attachment,
            ),
            $attachments,
        ));
    }
}
