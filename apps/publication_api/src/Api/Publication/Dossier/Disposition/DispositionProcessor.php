<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Disposition;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierProcessor;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DossierService;
use Shared\Service\MainDocumentService;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

final class DispositionProcessor extends AbstractDossierProcessor
{
    public function __construct(
        AttachmentService $attachmentService,
        DepartmentRepository $departmentRepository,
        DossierDispatcher $dossierDispatcher,
        DossierService $dossierService,
        MainDocumentService $mainDocumentService,
        OrganisationRepository $organisationRepository,
        SubjectRepository $subjectRepository,
        private readonly DispositionRepository $dispositionRepository,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?DispositionDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, DispositionRequestDto::class);

        $dispositionExternalId = $uriVariables['dispositionExternalId'];
        Assert::string($dispositionExternalId);

        $organisation = $this->getOrganisation($uriVariables);
        $subject = $this->getSubject($data, $organisation);
        $department = $this->getDepartment($organisation, $data->departmentId);
        $disposition = $this->dispositionRepository->findByOrganisationAndExternalId($organisation, $dispositionExternalId);

        if ($disposition === null) {
            $disposition = $this->create($organisation, $department, $subject, $data, $dispositionExternalId);

            return DispositionMapper::fromEntity($disposition);
        }

        $this->update($disposition, $organisation, $department, $subject, $data);

        return DispositionMapper::fromEntity($disposition);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        DispositionRequestDto $dispositionRequestDto,
        string $dispositionExternalId,
    ): Disposition {
        $disposition = DispositionMapper::create($dispositionRequestDto, $organisation, $department, $subject, $dispositionExternalId);
        $mainDocument = DispositionMainDocumentMapper::create($disposition, $dispositionRequestDto->mainDocument);
        $attachments = $this->getAttachments($disposition, $dispositionRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $disposition->setMainDocument($mainDocument);
        $this->addAttachments($disposition, $attachments);

        $this->validateDossier($disposition);
        $this->dispatchCreateDossierCommand($disposition);

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
        $mainDocument = DispositionMainDocumentMapper::update($disposition, $dispositionRequestDto->mainDocument);
        $attachments = $this->getAttachments($disposition, $dispositionRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $disposition->setMainDocument($mainDocument);
        $this->removeDossierAttachments($disposition);
        $this->addAttachments($disposition, $attachments);

        $this->validateDossier($disposition);
        $this->dispatchUpdateDossierCommand($disposition);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<DispositionAttachment>
     */
    private function getAttachments(Disposition $disposition, array $attachments): array
    {
        return array_values(array_map(fn (AttachmentRequestDto $attachment): DispositionAttachment => DispositionAttachmentMapper::create(
            $disposition,
            $attachment,
        ), $attachments));
    }
}
