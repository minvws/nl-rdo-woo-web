<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierNrValidator;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Document\DocumentPrefixDeterminer;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements ProcessorInterface<AnnualReportRequestDto,?AnnualReportResponseDto>
 */
final readonly class AnnualReportProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNrValidator $dossierNrValidator,
        private DossierSupportService $dossierSupportService,
        private AnnualReportRepository $annualReportRepository,
        private AnnualReportMapper $annualReportMapper,
        private DocumentPrefixDeterminer $documentPrefixDeterminer,
        private AttachmentSynchronizer $attachmentSynchronizer,
        private OrganisationResolver $organisationResolver,
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
        $annualReport = $this->annualReportRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);

        if ($annualReport === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNrValidator->validate($data->dossierNumber, $documentPrefix);
            $annualReport = $this->create($organisation, $department, $subject, $data, $dossierExternalId, $documentPrefix);

            return $this->annualReportMapper->fromEntity($annualReport);
        }

        $this->dossierNrValidator->validate($data->dossierNumber, $annualReport->getDocumentPrefix(), $annualReport->getId());
        $this->update($annualReport, $organisation, $department, $subject, $data);

        return $this->annualReportMapper->fromEntity($annualReport);
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
        $mainDocument = AnnualReportMainDocumentMapper::create($annualReport, $annualReportRequestDto->mainDocument);
        $attachments = $this->getAttachments($annualReport, $annualReportRequestDto->attachments);

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);

        $annualReport->setMainDocument($mainDocument);
        $this->dossierSupportService->addAttachments($annualReport, $attachments);

        $this->dossierSupportService->validateDossier($annualReport);
        $this->dossierSupportService->dispatchCreateDossierCommand($annualReport);

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

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);

        $annualReport->setMainDocument($mainDocument);
        $this->attachmentSynchronizer->sync($annualReport, $annualReportRequestDto->attachments);

        $this->dossierSupportService->validateDossier($annualReport);
        $this->dossierSupportService->dispatchUpdateDossierCommand($annualReport);
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
