<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\ComplaintJudgement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use PublicationApi\Api\Publication\Dossier\AbstractDossierProcessor;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DossierService;
use Shared\Service\MainDocumentService;
use Webmozart\Assert\Assert;

final class ComplaintJudgementProcessor extends AbstractDossierProcessor
{
    public function __construct(
        AttachmentService $attachmentService,
        DepartmentRepository $departmentRepository,
        DossierDispatcher $dossierDispatcher,
        DossierService $dossierService,
        MainDocumentService $mainDocumentService,
        OrganisationRepository $organisationRepository,
        SubjectRepository $subjectRepository,
        private readonly ComplaintJudgementRepository $complaintJudgementRepository,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?ComplaintJudgementDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        $complaintJudgementExternalId = $uriVariables['complaintJudgementExternalId'];
        Assert::string($complaintJudgementExternalId);

        Assert::isInstanceOf($data, ComplaintJudgementRequestDto::class);

        $organisation = $this->getOrganisation($uriVariables);
        $subject = $this->getSubject($data, $organisation);
        $department = $this->getDepartment($organisation, $data->departmentId);
        $complaintJudgement = $this->complaintJudgementRepository->findByOrganisationAndExternalId($organisation, $complaintJudgementExternalId);

        if (! $complaintJudgement instanceof ComplaintJudgement) {
            $complaintJudgement = $this->create($organisation, $department, $subject, $data, $complaintJudgementExternalId);

            return ComplaintJudgementMapper::fromEntity($complaintJudgement);
        }

        $this->update($complaintJudgement, $organisation, $department, $subject, $data);

        return ComplaintJudgementMapper::fromEntity($complaintJudgement);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ComplaintJudgementRequestDto $complaintJudgementRequestDto,
        string $complaintJudgementExternalId,
    ): ComplaintJudgement {
        $complaintJudgement = ComplaintJudgementMapper::create(
            $complaintJudgementRequestDto,
            $organisation,
            $department,
            $subject,
            $complaintJudgementExternalId,
        );
        $mainDocument = ComplaintJudgementMainDocumentMapper::create($complaintJudgement, $complaintJudgementRequestDto->mainDocument);

        $this->validateMainDocument($mainDocument);

        $complaintJudgement->setMainDocument($mainDocument);

        $this->validateDossier($complaintJudgement);
        $this->dispatchCreateDossierCommand($complaintJudgement);

        return $complaintJudgement;
    }

    private function update(
        ComplaintJudgement $complaintJudgement,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ComplaintJudgementRequestDto $complaintJudgementRequestDto,
    ): void {
        $complaintJudgement = ComplaintJudgementMapper::update(
            $complaintJudgement,
            $complaintJudgementRequestDto,
            $organisation,
            $department,
            $subject,
        );
        $mainDocument = ComplaintJudgementMainDocumentMapper::update($complaintJudgement, $complaintJudgementRequestDto->mainDocument);

        $this->validateMainDocument($mainDocument);

        $complaintJudgement->setMainDocument($mainDocument);

        $this->validateDossier($complaintJudgement);
        $this->dispatchUpdateDossierCommand($complaintJudgement);
    }
}
