<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\InvestigationReport;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierProcessor;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DossierService;
use Shared\Service\MainDocumentService;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

final class InvestigationReportProcessor extends AbstractDossierProcessor
{
    public function __construct(
        AttachmentService $attachmentService,
        DepartmentRepository $departmentRepository,
        DossierDispatcher $dossierDispatcher,
        DossierService $dossierService,
        MainDocumentService $mainDocumentService,
        OrganisationRepository $organisationRepository,
        SubjectRepository $subjectRepository,
        private readonly InvestigationReportRepository $investigationReportRepository,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?InvestigationReportDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, InvestigationReportRequestDto::class);

        $investigationReportExternalId = $uriVariables['investigationReportExternalId'];
        Assert::string($investigationReportExternalId);

        $organisation = $this->getOrganisation($uriVariables);
        $subject = $this->getSubject($data, $organisation);
        $department = $this->getDepartment($organisation, $data->departmentId);
        $investigationReport = $this->investigationReportRepository->findByOrganisationAndExternalId($organisation, $investigationReportExternalId);

        if ($investigationReport === null) {
            $investigationReport = $this->create($organisation, $department, $subject, $data, $investigationReportExternalId);

            return InvestigationReportMapper::fromEntity($investigationReport);
        }

        $this->update($investigationReport, $organisation, $department, $subject, $data);

        return InvestigationReportMapper::fromEntity($investigationReport);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        InvestigationReportRequestDto $investigationReportRequestDto,
        string $investigationReportExternalId,
    ): InvestigationReport {
        $investigationReport = InvestigationReportMapper::create(
            $investigationReportRequestDto,
            $organisation,
            $department,
            $subject,
            $investigationReportExternalId
        );
        $mainDocument = InvestigationReportMainDocumentMapper::create($investigationReport, $investigationReportRequestDto->mainDocument);
        $attachments = $this->getAttachments($investigationReport, $investigationReportRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $investigationReport->setMainDocument($mainDocument);
        $this->addAttachments($investigationReport, $attachments);

        $this->validateDossier($investigationReport);
        $this->dispatchCreateDossierCommand($investigationReport);

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

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $investigationReport->setMainDocument($mainDocument);
        $this->removeDossierAttachments($investigationReport);
        $this->addAttachments($investigationReport, $attachments);

        $this->validateDossier($investigationReport);
        $this->dispatchUpdateDossierCommand($investigationReport);
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
