<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\RequestForAdvice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\AbstractDossierProcessor;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DossierService;
use Shared\Service\MainDocumentService;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

final class RequestForAdviceProcessor extends AbstractDossierProcessor
{
    public function __construct(
        AttachmentService $attachmentService,
        DepartmentRepository $departmentRepository,
        DossierDispatcher $dossierDispatcher,
        DossierService $dossierService,
        MainDocumentService $mainDocumentService,
        OrganisationRepository $organisationRepository,
        SubjectRepository $subjectRepository,
        private readonly RequestForAdviceRepository $requestForAdviceRepository,
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?RequestForAdviceDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, RequestForAdviceRequestDto::class);

        $requestForAdviceExternalId = $uriVariables['requestForAdviceExternalId'];
        Assert::string($requestForAdviceExternalId);

        $organisation = $this->getOrganisation($uriVariables);
        $subject = $this->getSubject($data, $organisation);
        $department = $this->getDepartment($organisation, $data->departmentId);
        $requestForAdvice = $this->requestForAdviceRepository->findByOrganisationAndExternalId($organisation, $requestForAdviceExternalId);

        if ($requestForAdvice === null) {
            $requestForAdvice = $this->create($organisation, $department, $subject, $data, $requestForAdviceExternalId);

            return RequestForAdviceMapper::fromEntity($requestForAdvice);
        }

        $this->update($requestForAdvice, $organisation, $department, $subject, $data);

        return RequestForAdviceMapper::fromEntity($requestForAdvice);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        RequestForAdviceRequestDto $requestForAdviceRequestDto,
        string $requestForAdviceExternalId,
    ): RequestForAdvice {
        $requestForAdvice = RequestForAdviceMapper::create(
            $requestForAdviceRequestDto,
            $organisation,
            $department,
            $subject,
            $requestForAdviceExternalId
        );
        $mainDocument = RequestForAdviceMainDocumentMapper::create($requestForAdvice, $requestForAdviceRequestDto->mainDocument);
        $attachments = $this->getAttachments($requestForAdvice, $requestForAdviceRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $requestForAdvice->setMainDocument($mainDocument);
        $this->addAttachments($requestForAdvice, $attachments);

        $this->validateDossier($requestForAdvice);
        $this->dispatchCreateDossierCommand($requestForAdvice);

        return $requestForAdvice;
    }

    private function update(
        RequestForAdvice $requestForAdvice,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        RequestForAdviceRequestDto $requestForAdviceRequestDto,
    ): void {
        $requestForAdvice = RequestForAdviceMapper::update($requestForAdvice, $requestForAdviceRequestDto, $organisation, $department, $subject);
        $mainDocument = RequestForAdviceMainDocumentMapper::update($requestForAdvice, $requestForAdviceRequestDto->mainDocument);
        $attachments = $this->getAttachments($requestForAdvice, $requestForAdviceRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $requestForAdvice->setMainDocument($mainDocument);
        $this->removeDossierAttachments($requestForAdvice);
        $this->addAttachments($requestForAdvice, $attachments);

        $this->validateDossier($requestForAdvice);
        $this->dispatchUpdateDossierCommand($requestForAdvice);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<RequestForAdviceAttachment>
     */
    private function getAttachments(RequestForAdvice $requestForAdvice, array $attachments): array
    {
        return array_values(array_map(fn (AttachmentRequestDto $attachment): RequestForAdviceAttachment => RequestForAdviceAttachmentMapper::create(
            $requestForAdvice,
            $attachment,
        ), $attachments));
    }
}
