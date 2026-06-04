<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierNrValidator;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Document\DocumentPrefixDeterminer;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements ProcessorInterface<RequestForAdviceRequestDto,?RequestForAdviceResponseDto>
 */
final readonly class RequestForAdviceProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNrValidator $dossierNrValidator,
        private DossierSupportService $dossierSupportService,
        private RequestForAdviceRepository $requestForAdviceRepository,
        private RequestForAdviceMapper $requestForAdviceMapper,
        private DocumentPrefixDeterminer $documentPrefixDeterminer,
        private AttachmentSynchronizer $attachmentSynchronizer,
        private OrganisationResolver $organisationResolver,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?RequestForAdviceResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, RequestForAdviceRequestDto::class);

        $requestForAdviceExternalId = $uriVariables['dossierExternalId'];
        Assert::string($requestForAdviceExternalId);
        $requestForAdviceExternalId = ExternalId::create($requestForAdviceExternalId);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $requestForAdvice = $this->requestForAdviceRepository->findByOrganisationAndExternalId($organisation, $requestForAdviceExternalId);

        if ($requestForAdvice === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNrValidator->validate($data->dossierNumber, $documentPrefix);
            $requestForAdvice = $this->create($organisation, $department, $subject, $data, $requestForAdviceExternalId, $documentPrefix);

            return $this->requestForAdviceMapper->fromEntity($requestForAdvice);
        }

        $this->dossierNrValidator->validate($data->dossierNumber, $requestForAdvice->getDocumentPrefix(), $requestForAdvice->getId());
        $this->update($requestForAdvice, $organisation, $department, $subject, $data);

        return $this->requestForAdviceMapper->fromEntity($requestForAdvice);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        RequestForAdviceRequestDto $requestForAdviceRequestDto,
        ExternalId $requestForAdviceExternalId,
        string $documentPrefix,
    ): RequestForAdvice {
        $requestForAdvice = RequestForAdviceMapper::create(
            $requestForAdviceRequestDto,
            $organisation,
            $department,
            $subject,
            $requestForAdviceExternalId,
            $documentPrefix,
        );
        $mainDocument = RequestForAdviceMainDocumentMapper::create($requestForAdvice, $requestForAdviceRequestDto->mainDocument);
        $attachments = $this->getAttachments($requestForAdvice, $requestForAdviceRequestDto->attachments);

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);

        $requestForAdvice->setMainDocument($mainDocument);
        $this->dossierSupportService->addAttachments($requestForAdvice, $attachments);

        $this->dossierSupportService->validateDossier($requestForAdvice);
        $this->dossierSupportService->dispatchCreateDossierCommand($requestForAdvice);

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

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);

        $requestForAdvice->setMainDocument($mainDocument);
        $this->attachmentSynchronizer->sync($requestForAdvice, $requestForAdviceRequestDto->attachments);

        $this->dossierSupportService->validateDossier($requestForAdvice);
        $this->dossierSupportService->dispatchUpdateDossierCommand($requestForAdvice);
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
