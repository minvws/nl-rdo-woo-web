<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

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
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements ProcessorInterface<AnnualReportRequestDto,?AnnualReportResponseDto>
 */
final readonly class AnnualReportProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNumberValidator $dossierNumberValidator,
        private DossierSupportService $dossierSupportService,
        private DossierAttachmentValidator $dossierAttachmentValidator,
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private DossierUpdateGuard $dossierUpdateGuard,
        private DossierRepository $dossierRepository,
        private DossierValidator $dossierValidator,
        private AnnualReportMapper $annualReportMapper,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?AnnualReportResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, AnnualReportRequestDto::class);
        Assert::string($uriVariables['dossierExternalId']);
        $dossierExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $dossier = $this->dossierRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);

        if ($dossier !== null && ! $dossier instanceof AnnualReport) {
            throw ExternalIdInUseException::forExternalIdAlreadyUsed($dossier->getType());
        }

        if ($dossier === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNumberValidator->validate($data->dossierNumber, $documentPrefix);
            $dossier = $this->create($organisation, $department, $subject, $data, $dossierExternalId, $documentPrefix);

            return $this->annualReportMapper->fromEntity($dossier);
        }

        $this->dossierUpdateGuard->assertDossierIsEditable($dossier);

        $this->dossierNumberValidator->validate($data->dossierNumber, $dossier->getDocumentPrefix(), $dossier->getId());
        $this->update($dossier, $organisation, $department, $subject, $data);

        return $this->annualReportMapper->fromEntity($dossier);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        AnnualReportRequestDto $annualReportRequestDto,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): AnnualReport {
        $annualReport = AnnualReportMapper::create(
            $annualReportRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        if ($annualReportRequestDto->mainDocument !== null) {
            $mainDocument = AnnualReportMainDocumentMapper::create($annualReport, $annualReportRequestDto->mainDocument);
            $annualReport->setMainDocument($mainDocument);
            $this->dossierMainDocumentValidator->validate($mainDocument);
        } else {
            $noticeNotPublic = $annualReportRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublic);

            $annualReport->setNoticeNotPublic(
                NoticeNotPublicMapper::create($annualReport, $noticeNotPublic),
            );
        }

        $this->dossierAttachmentValidator->assertUniqueExternalIds($annualReportRequestDto->attachments);
        $attachments = $this->getAttachments($annualReport, $annualReportRequestDto->attachments);
        $this->dossierAttachmentValidator->validate($attachments, $annualReport->getStatus());
        $this->dossierSupportService->addAttachments($annualReport, $attachments);

        $this->dossierValidator->validateDossier($annualReport);
        $this->dossierSupportService->autoPublish($annualReport);
        $this->dossierSupportService->validateCompletionAndPersist($annualReport);
        $this->dossierSupportService->synchronizeArtifacts($annualReport);

        return $annualReport;
    }

    private function update(
        AnnualReport $annualReport,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        AnnualReportRequestDto $annualReportRequestDto,
    ): void {
        $annualReport = AnnualReportMapper::update($annualReport, $annualReportRequestDto, $organisation, $department, $subject);

        if ($annualReportRequestDto->mainDocument !== null) {
            if ($annualReport->getNoticeNotPublic() !== null) {
                $this->noticeNotPublicService->deleteFromDossier($annualReport);
            }

            $mainDocument = $annualReport->getMainDocument() !== null
                ? AnnualReportMainDocumentMapper::update($annualReport, $annualReportRequestDto->mainDocument)
                : AnnualReportMainDocumentMapper::create($annualReport, $annualReportRequestDto->mainDocument);
            $annualReport->setMainDocument($mainDocument);
            $this->dossierMainDocumentValidator->validate($mainDocument);
        } else {
            if ($annualReport->getMainDocument() !== null) {
                $this->messageBus->dispatch(new DeleteMainDocumentCommand($annualReport->getId()));
            }

            $noticeNotPublicDto = $annualReportRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublicDto);

            $notice = $annualReport->getNoticeNotPublic() !== null
                ? $this->noticeNotPublicService->updateForDossier($annualReport, $noticeNotPublicDto)
                : $this->noticeNotPublicService->createForDossier($annualReport, $noticeNotPublicDto);
            $annualReport->setNoticeNotPublic($notice);
        }

        $this->dossierAttachmentValidator->assertUniqueExternalIds($annualReportRequestDto->attachments);
        $this->dossierAttachmentValidator->assertNoAttachmentRemovalInNonConcept($annualReport, $annualReportRequestDto->attachments);
        $attachments = $this->getAttachments($annualReport, $annualReportRequestDto->attachments);
        $this->dossierAttachmentValidator->validate($attachments, $annualReport->getStatus());
        $this->attachmentSynchronizer->sync($annualReport, $annualReportRequestDto->attachments);

        $this->dossierValidator->validateDossier($annualReport);
        $this->dossierSupportService->autoPublish($annualReport);
        $this->dossierSupportService->validateCompletionAndPersist($annualReport);
        $this->dossierSupportService->synchronizeArtifacts($annualReport);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<AnnualReportAttachment>
     */
    private function getAttachments(AnnualReport $annualReport, array $attachments): array
    {
        return array_values(array_map(static fn (AttachmentRequestDto $attachment): AnnualReportAttachment => AnnualReportAttachmentMapper::create(
            $annualReport,
            $attachment,
        ), $attachments));
    }
}
