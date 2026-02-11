<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Covenant;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierProcessor;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DossierService;
use Shared\Service\MainDocumentService;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

final class CovenantProcessor extends AbstractDossierProcessor
{
    public function __construct(
        AttachmentService $attachmentService,
        DepartmentRepository $departmentRepository,
        DossierDispatcher $dossierDispatcher,
        DossierService $dossierService,
        MainDocumentService $mainDocumentService,
        OrganisationRepository $organisationRepository,
        SubjectRepository $subjectRepository,
        private readonly CovenantRepository $covenantRepository,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?CovenantDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, CovenantRequestDto::class);

        $covenantExternalId = $uriVariables['covenantExternalId'];
        Assert::string($covenantExternalId);

        $organisation = $this->getOrganisation($uriVariables);
        $subject = $this->getSubject($data, $organisation);
        $department = $this->getDepartment($organisation, $data->departmentId);
        $covenant = $this->covenantRepository->findByOrganisationAndExternalId($organisation, $covenantExternalId);

        if ($covenant === null) {
            $covenant = $this->create($organisation, $department, $subject, $data, $covenantExternalId);

            return CovenantMapper::fromEntity($covenant);
        }

        $this->update($covenant, $organisation, $department, $subject, $data);

        return CovenantMapper::fromEntity($covenant);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        CovenantRequestDto $covenantRequestDto,
        string $covenantExternalId,
    ): Covenant {
        $covenant = CovenantMapper::create(
            $covenantRequestDto,
            $organisation,
            $department,
            $subject,
            $covenantExternalId
        );
        $mainDocument = CovenantMainDocumentMapper::create($covenant, $covenantRequestDto->mainDocument);
        $attachments = $this->getAttachments($covenant, $covenantRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $covenant->setMainDocument($mainDocument);
        $this->addAttachments($covenant, $attachments);

        $this->validateDossier($covenant);
        $this->dispatchCreateDossierCommand($covenant);

        return $covenant;
    }

    private function update(
        Covenant $covenant,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        CovenantRequestDto $covenantRequestDto,
    ): void {
        $covenant = CovenantMapper::update($covenant, $covenantRequestDto, $organisation, $department, $subject);
        $mainDocument = CovenantMainDocumentMapper::update($covenant, $covenantRequestDto->mainDocument);
        $attachments = $this->getAttachments($covenant, $covenantRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $covenant->setMainDocument($mainDocument);
        $this->removeDossierAttachments($covenant);
        $this->addAttachments($covenant, $attachments);

        $this->validateDossier($covenant);
        $this->dispatchUpdateDossierCommand($covenant);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<CovenantAttachment>
     */
    private function getAttachments(Covenant $covenant, array $attachments): array
    {
        return array_values(array_map(fn (AttachmentRequestDto $attachment): CovenantAttachment => CovenantAttachmentMapper::create(
            $covenant,
            $attachment,
        ), $attachments));
    }
}
