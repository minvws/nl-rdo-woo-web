<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\AnnualReport;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierProcessor;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DossierService;
use Shared\Service\MainDocumentService;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

final class AnnualReportProcessor extends AbstractDossierProcessor
{
    public function __construct(
        AttachmentService $attachmentService,
        DepartmentRepository $departmentRepository,
        DossierDispatcher $dossierDispatcher,
        DossierService $dossierService,
        MainDocumentService $mainDocumentService,
        OrganisationRepository $organisationRepository,
        SubjectRepository $subjectRepository,
        private readonly AnnualReportRepository $annualReportRepository,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?AnnualReportDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, AnnualReportRequestDto::class);

        $annualReportExternalId = $uriVariables['annualReportExternalId'];
        Assert::string($annualReportExternalId);

        $organisation = $this->getOrganisation($uriVariables);
        $subject = $this->getSubject($data, $organisation);
        $department = $this->getDepartment($organisation, $data->departmentId);
        $annualReport = $this->annualReportRepository->findByOrganisationAndExternalId($organisation, $annualReportExternalId);

        if ($annualReport === null) {
            $annualReport = $this->create($organisation, $department, $subject, $data, $annualReportExternalId);

            return AnnualReportMapper::fromEntity($annualReport);
        }

        $this->update($annualReport, $organisation, $department, $subject, $data);

        return AnnualReportMapper::fromEntity($annualReport);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        AnnualReportRequestDto $annualReportRequestDto,
        string $annualReportExternalId,
    ): AnnualReport {
        $annualReport = AnnualReportMapper::create($annualReportRequestDto, $organisation, $department, $subject, $annualReportExternalId);
        $mainDocument = AnnualReportMainDocumentMapper::create($annualReport, $annualReportRequestDto->mainDocument);
        $attachments = $this->getAttachments($annualReport, $annualReportRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $annualReport->setMainDocument($mainDocument);
        $this->addAttachments($annualReport, $attachments);

        $this->validateDossier($annualReport);
        $this->dispatchCreateDossierCommand($annualReport);

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
        $mainDocument = AnnualReportMainDocumentMapper::update($annualReport, $annualReportRequestDto->mainDocument);
        $attachments = $this->getAttachments($annualReport, $annualReportRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $annualReport->setMainDocument($mainDocument);
        $this->removeDossierAttachments($annualReport);
        $this->addAttachments($annualReport, $attachments);

        $this->validateDossier($annualReport);
        $this->dispatchUpdateDossierCommand($annualReport);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<AnnualReportAttachment>
     */
    private function getAttachments(AnnualReport $annualReport, array $attachments): array
    {
        return array_values(array_map(fn (AttachmentRequestDto $attachment): AnnualReportAttachment => AnnualReportAttachmentMapper::create(
            $annualReport,
            $attachment,
        ), $attachments));
    }
}
