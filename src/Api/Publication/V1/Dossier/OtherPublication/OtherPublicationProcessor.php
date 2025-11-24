<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\OtherPublication;

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
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DossierService;
use Shared\Service\MainDocumentService;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
final class OtherPublicationProcessor extends AbstractDossierProcessor
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
        private readonly OtherPublicationRepository $otherPublicationRepository,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?OtherPublicationDto
    {
        unset($context);
        Assert::isInstanceOf($data, AbstractDossierRequestDto::class);

        $organisation = $this->getOrganisation($uriVariables);
        $subject = $this->getSubject($data, $organisation);
        $department = $this->getDepartment($organisation, $data->departmentId);

        if ($operation instanceof Post) {
            Assert::isInstanceOf($data, OtherPublicationCreateRequestDto::class);
            $otherPublication = $this->create($organisation, $department, $subject, $data);

            return OtherPublicationMapper::fromEntity($otherPublication);
        }

        if ($operation instanceof Put) {
            Assert::isInstanceOf($data, OtherPublicationUpdateRequestDto::class);
            $otherPublication = $this->otherPublicationRepository->find($uriVariables['otherPublicationId']);
            Assert::isInstanceOf($otherPublication, OtherPublication::class);

            $this->update($otherPublication, $organisation, $department, $subject, $data);

            return OtherPublicationMapper::fromEntity($otherPublication);
        }

        return null;
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        OtherPublicationCreateRequestDto $otherPublicationCreateRequestDto,
    ): OtherPublication {
        $otherPublication = OtherPublicationMapper::create($otherPublicationCreateRequestDto, $organisation, $department, $subject);
        $mainDocument = OtherPublicationMainDocumentMapper::create($otherPublication, $otherPublicationCreateRequestDto->mainDocument);
        $attachments = $this->getAttachments($otherPublication, $otherPublicationCreateRequestDto->attachments);

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
        OtherPublicationUpdateRequestDto $otherPublicationUpdateRequestDto,
    ): void {
        $otherPublication = OtherPublicationMapper::update(
            $otherPublication,
            $otherPublicationUpdateRequestDto,
            $organisation,
            $department,
            $subject,
        );
        $mainDocument = OtherPublicationMainDocumentMapper::update($otherPublication, $otherPublicationUpdateRequestDto->mainDocument);
        $attachments = $this->getAttachments($otherPublication, $otherPublicationUpdateRequestDto->attachments);

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
     * @return array<array-key,OtherPublicationAttachment>
     */
    private function getAttachments(OtherPublication $otherPublication, array $attachments): array
    {
        return array_map(fn (AttachmentRequestDto $attachment): OtherPublicationAttachment => OtherPublicationAttachmentMapper::create(
            $otherPublication,
            $attachment,
        ), $attachments);
    }
}
