<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Advice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierProcessor;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DossierService;
use Shared\Service\MainDocumentService;
use Shared\ValueObject\ExternalId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\ConstraintViolationList;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function sprintf;

final class AdviceProcessor extends AbstractDossierProcessor
{
    public function __construct(
        AttachmentService $attachmentService,
        DepartmentRepository $departmentRepository,
        DossierDispatcher $dossierDispatcher,
        DossierService $dossierService,
        MainDocumentService $mainDocumentService,
        OrganisationRepository $organisationRepository,
        SubjectRepository $subjectRepository,
        private readonly AdviceRepository $adviceRepository,
        private readonly Security $security,
        private readonly AdviceMapper $adviceMapper,
    ) {
        parent::__construct(
            $attachmentService,
            $departmentRepository,
            $dossierDispatcher,
            $dossierService,
            $mainDocumentService,
            $organisationRepository,
            $subjectRepository,
            $this->security,
        );
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?AdviceDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, AdviceRequestDto::class);

        $adviceExternalId = $uriVariables['adviceExternalId'];
        Assert::string($adviceExternalId);
        $adviceExternalId = ExternalId::create($adviceExternalId);

        $organisation = $this->getOrganisation($uriVariables);
        $subject = $this->getSubject($data, $organisation);
        $department = $this->getDepartment($organisation, $data->departmentId);
        $advice = $this->adviceRepository->findByOrganisationAndExternalId($organisation, $adviceExternalId);

        if ($advice === null) {
            $advice = $this->create($organisation, $department, $subject, $data, $adviceExternalId);

            return $this->adviceMapper->fromEntity($advice);
        }

        $this->update($advice, $organisation, $department, $subject, $data);

        return $this->adviceMapper->fromEntity($advice);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        AdviceRequestDto $adviceRequestDto,
        ExternalId $adviceExternalId,
    ): Advice {
        $advice = AdviceMapper::create(
            $adviceRequestDto,
            $organisation,
            $department,
            $subject,
            $adviceExternalId
        );
        $mainDocument = AdviceMainDocumentMapper::create($advice, $adviceRequestDto->mainDocument);
        $attachments = $this->getAttachments($advice, $adviceRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAdviceAttachments($attachments);

        $advice->setMainDocument($mainDocument);
        $this->addAttachments($advice, $attachments);

        $this->validateDossier($advice);
        $this->dispatchCreateDossierCommand($advice);

        return $advice;
    }

    private function update(
        Advice $advice,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        AdviceRequestDto $adviceRequestDto,
    ): void {
        $advice = AdviceMapper::update($advice, $adviceRequestDto, $organisation, $department, $subject);
        $mainDocument = AdviceMainDocumentMapper::update($advice, $adviceRequestDto->mainDocument);
        $attachments = $this->getAttachments($advice, $adviceRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAdviceAttachments($attachments);

        $advice->setMainDocument($mainDocument);
        $this->removeDossierAttachments($advice);
        $this->addAttachments($advice, $attachments);

        $this->validateDossier($advice);
        $this->dispatchUpdateDossierCommand($advice);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<AdviceAttachment>
     */
    private function getAttachments(Advice $advice, array $attachments): array
    {
        return array_values(array_map(fn (AttachmentRequestDto $attachment): AdviceAttachment => AdviceAttachmentMapper::create(
            $advice,
            $attachment,
        ), $attachments));
    }

    /**
     * @param list<AdviceAttachment> $attachments
     */
    protected function validateAdviceAttachments(array $attachments): void
    {
        $attachmentType = AttachmentType::REQUEST_FOR_ADVICE;
        if ($this->hasMoreThanOneAttachmentOfType($attachments, $attachmentType)) {
            throw new ValidationException(ConstraintViolationList::createFromMessage(sprintf(
                'dossier should have at most one attachment of type "%s"',
                $attachmentType->value,
            )));
        }

        $this->validateAttachments($attachments);
    }

    /**
     * @param list<AdviceAttachment> $attachments
     */
    private function hasMoreThanOneAttachmentOfType(array $attachments, AttachmentType $attachmentType): bool
    {
        return count(array_filter($attachments, fn (AdviceAttachment $attachment): bool => $attachment->getType() === $attachmentType)) > 1;
    }
}
