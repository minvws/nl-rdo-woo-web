<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\Disposition;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\EntityManagerInterface;
use Shared\Api\Publication\V1\Attachment\AttachmentRequestDto;
use Shared\Api\Publication\V1\Dossier\AbstractDossierProcessor;
use Shared\Api\Publication\V1\Dossier\AbstractDossierRequestDto;
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

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
final class DispositionProcessor extends AbstractDossierProcessor
{
    public function __construct(
        AttachmentService $attachmentService,
        DepartmentRepository $departmentRepository,
        DossierDispatcher $dossierDispatcher,
        DossierService $dossierService,
        EntityManagerInterface $entityManagerInterface,
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
            $entityManagerInterface,
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
        Assert::isInstanceOf($data, AbstractDossierRequestDto::class);

        $organisation = $this->getOrganisation($uriVariables);
        $subject = $this->getSubject($data, $organisation);
        $department = $this->getDepartment($organisation, $data->departmentId);

        if ($operation instanceof Post) {
            Assert::isInstanceOf($data, DispositionCreateRequestDto::class);
            $disposition = $this->create($organisation, $department, $subject, $data);

            return DispositionMapper::fromEntity($disposition);
        }

        if ($operation instanceof Put) {
            Assert::isInstanceOf($data, DispositionUpdateRequestDto::class);
            $disposition = $this->dispositionRepository->find($uriVariables['dispositionId']);
            Assert::isInstanceOf($disposition, Disposition::class);

            $this->update($disposition, $organisation, $department, $subject, $data);

            return DispositionMapper::fromEntity($disposition);
        }

        return null;
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        DispositionCreateRequestDto $dispositionCreateRequestDto,
    ): Disposition {
        $disposition = DispositionMapper::create($dispositionCreateRequestDto, $organisation, $department, $subject);
        $mainDocument = DispositionMainDocumentMapper::create($disposition, $dispositionCreateRequestDto->mainDocument);
        $attachments = $this->getAttachments($disposition, $dispositionCreateRequestDto->attachments);

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
        DispositionUpdateRequestDto $dispositionUpdateRequestDto,
    ): void {
        $disposition = DispositionMapper::update($disposition, $dispositionUpdateRequestDto, $organisation, $department, $subject);
        $mainDocument = DispositionMainDocumentMapper::update($disposition, $dispositionUpdateRequestDto->mainDocument);
        $attachments = $this->getAttachments($disposition, $dispositionUpdateRequestDto->attachments);

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
     * @return array<array-key,DispositionAttachment>
     */
    private function getAttachments(Disposition $disposition, array $attachments): array
    {
        return array_map(fn (AttachmentRequestDto $attachment): DispositionAttachment => DispositionAttachmentMapper::create(
            $disposition,
            $attachment,
        ), $attachments);
    }
}
