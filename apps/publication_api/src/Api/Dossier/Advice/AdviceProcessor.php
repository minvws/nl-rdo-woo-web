<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException as ApiPlatformValidationException;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierNrValidator;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Document\DocumentPrefixDeterminer;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Validator\ConstraintViolationList;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function sprintf;

/**
 * @implements ProcessorInterface<AdviceRequestDto,?AdviceResponseDto>
 */
final readonly class AdviceProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNrValidator $dossierNrValidator,
        private DossierSupportService $dossierSupportService,
        private AdviceRepository $adviceRepository,
        private AdviceMapper $adviceMapper,
        private DocumentPrefixDeterminer $documentPrefixDeterminer,
        private AttachmentSynchronizer $attachmentSynchronizer,
        private OrganisationResolver $organisationResolver,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?AdviceResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, AdviceRequestDto::class);
        Assert::string($uriVariables['dossierExternalId']);
        $dossierExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $advice = $this->adviceRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);

        if ($advice === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNrValidator->validate($data->dossierNumber, $documentPrefix);
            $advice = $this->create($organisation, $department, $subject, $data, $dossierExternalId, $documentPrefix);

            return $this->adviceMapper->fromEntity($advice);
        }

        $this->dossierNrValidator->validate($data->dossierNumber, $advice->getDocumentPrefix(), $advice->getId());
        $this->update($advice, $organisation, $department, $subject, $data);

        return $this->adviceMapper->fromEntity($advice);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        AdviceRequestDto $adviceRequestDto,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): Advice {
        $advice = AdviceMapper::create(
            $adviceRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );
        $mainDocument = AdviceMainDocumentMapper::create($advice, $adviceRequestDto->mainDocument);
        $attachments = $this->getAttachments($advice, $adviceRequestDto->attachments);

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->validateAdviceAttachments($attachments);

        $advice->setMainDocument($mainDocument);
        $this->dossierSupportService->addAttachments($advice, $attachments);

        $this->dossierSupportService->validateDossier($advice);
        $this->dossierSupportService->dispatchCreateDossierCommand($advice);

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

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->validateAdviceAttachments($attachments);

        $advice->setMainDocument($mainDocument);
        $this->attachmentSynchronizer->sync($advice, $adviceRequestDto->attachments);

        $this->dossierSupportService->validateDossier($advice);
        $this->dossierSupportService->dispatchUpdateDossierCommand($advice);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<AdviceAttachment>
     */
    private function getAttachments(Advice $advice, array $attachments): array
    {
        return array_values(array_map(static fn (AttachmentRequestDto $attachment): AdviceAttachment => AdviceAttachmentMapper::create(
            $advice,
            $attachment,
        ), $attachments));
    }

    /**
     * @param list<AdviceAttachment> $attachments
     */
    private function validateAdviceAttachments(array $attachments): void
    {
        $attachmentType = AttachmentType::REQUEST_FOR_ADVICE;
        if ($this->hasMoreThanOneAttachmentOfType($attachments, $attachmentType)) {
            throw new ApiPlatformValidationException(ConstraintViolationList::createFromMessage(sprintf(
                'dossier should have at most one attachment of type "%s"',
                $attachmentType->value,
            )));
        }

        $this->dossierSupportService->validateAttachments($attachments);
    }

    /**
     * @param list<AdviceAttachment> $attachments
     */
    private function hasMoreThanOneAttachmentOfType(array $attachments, AttachmentType $attachmentType): bool
    {
        return count(array_filter($attachments, static fn (AdviceAttachment $attachment): bool => $attachment->getType() === $attachmentType)) > 1;
    }
}
