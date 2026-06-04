<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\InvestigationReport;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\DossierSupportService;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Document\DocumentPrefixDeterminer;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements ProcessorInterface<InvestigationReportRequestDto,?InvestigationReportResponseDto>
 */
final readonly class InvestigationReportProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierSupportService $dossierSupportService,
        private InvestigationReportRepository $investigationReportRepository,
        private InvestigationReportMapper $investigationReportMapper,
        private DocumentPrefixDeterminer $documentPrefixDeterminer,
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

        $dossierExternalId = $uriVariables['dossierExternalId'];
        Assert::string($dossierExternalId);
        $dossierExternalId = ExternalId::create($dossierExternalId);

        $organisation = $this->dossierSupportService->getOrganisation($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $investigationReport = $this->investigationReportRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);

        if ($investigationReport === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $investigationReport = $this->create($organisation, $department, $subject, $data, $dossierExternalId, $documentPrefix);

            return $this->investigationReportMapper->fromEntity($investigationReport);
        }

        $this->update($investigationReport, $organisation, $department, $subject, $data);

        return $this->investigationReportMapper->fromEntity($investigationReport);
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
        $mainDocument = InvestigationReportMainDocumentMapper::create($investigationReport, $investigationReportRequestDto->mainDocument);
        $attachments = $this->getAttachments($investigationReport, $investigationReportRequestDto->attachments);

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);

        $investigationReport->setMainDocument($mainDocument);
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
        $mainDocument = InvestigationReportMainDocumentMapper::update($investigationReport, $investigationReportRequestDto->mainDocument);
        $attachments = $this->getAttachments($investigationReport, $investigationReportRequestDto->attachments);

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);

        $investigationReport->setMainDocument($mainDocument);
        $this->dossierSupportService->removeDossierAttachments($investigationReport);
        $this->dossierSupportService->addAttachments($investigationReport, $attachments);

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
            fn (AttachmentRequestDto $attachment): InvestigationReportAttachment => InvestigationReportAttachmentMapper::create(
                $investigationReport,
                $attachment,
            ),
            $attachments,
        ));
    }
}
