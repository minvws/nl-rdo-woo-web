<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\OtherPublication;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierProcessor;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DossierService;
use Shared\Service\MainDocumentService;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

final class OtherPublicationProcessor extends AbstractDossierProcessor
{
    public function __construct(
        AttachmentService $attachmentService,
        DepartmentRepository $departmentRepository,
        DossierDispatcher $dossierDispatcher,
        DossierService $dossierService,
        MainDocumentService $mainDocumentService,
        OrganisationRepository $organisationRepository,
        SubjectRepository $subjectRepository,
        private readonly OtherPublicationRepository $otherPublicationRepository,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?OtherPublicationDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, OtherPublicationRequestDto::class);

        $otherPublicationExternalId = $uriVariables['otherPublicationExternalId'];
        Assert::string($otherPublicationExternalId);

        $organisation = $this->getOrganisation($uriVariables);
        $subject = $this->getSubject($data, $organisation);
        $department = $this->getDepartment($organisation, $data->departmentId);
        $otherPublication = $this->otherPublicationRepository->findByOrganisationAndExternalId($organisation, $otherPublicationExternalId);

        if ($otherPublication === null) {
            $otherPublication = $this->create($organisation, $department, $subject, $data, $otherPublicationExternalId);

            return OtherPublicationMapper::fromEntity($otherPublication);
        }

        $this->update($otherPublication, $organisation, $department, $subject, $data);

        return OtherPublicationMapper::fromEntity($otherPublication);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        OtherPublicationRequestDto $otherPublicationRequestDto,
        string $otherPublicationExternalId,
    ): OtherPublication {
        $otherPublication = OtherPublicationMapper::create(
            $otherPublicationRequestDto,
            $organisation,
            $department,
            $subject,
            $otherPublicationExternalId
        );
        $mainDocument = OtherPublicationMainDocumentMapper::create($otherPublication, $otherPublicationRequestDto->mainDocument);
        $attachments = $this->getAttachments($otherPublication, $otherPublicationRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $otherPublication->setMainDocument($mainDocument);
        $this->addAttachments($otherPublication, $attachments);

        $this->validateDossier($otherPublication);
        $this->dispatchCreateDossierCommand($otherPublication);

        return $otherPublication;
    }

    private function update(
        OtherPublication $otherPublication,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        OtherPublicationRequestDto $otherPublicationRequestDto,
    ): void {
        $otherPublication = OtherPublicationMapper::update($otherPublication, $otherPublicationRequestDto, $organisation, $department, $subject);
        $mainDocument = OtherPublicationMainDocumentMapper::update($otherPublication, $otherPublicationRequestDto->mainDocument);
        $attachments = $this->getAttachments($otherPublication, $otherPublicationRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $otherPublication->setMainDocument($mainDocument);
        $this->removeDossierAttachments($otherPublication);
        $this->addAttachments($otherPublication, $attachments);

        $this->validateDossier($otherPublication);
        $this->dispatchUpdateDossierCommand($otherPublication);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<OtherPublicationAttachment>
     */
    private function getAttachments(OtherPublication $otherPublication, array $attachments): array
    {
        return array_values(array_map(fn (AttachmentRequestDto $attachment): OtherPublicationAttachment => OtherPublicationAttachmentMapper::create(
            $otherPublication,
            $attachment,
        ), $attachments));
    }
}
