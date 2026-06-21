<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierNrValidator;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Document\DocumentPrefixDeterminer;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements ProcessorInterface<DispositionRequestDto,?DispositionResponseDto>
 */
final readonly class DispositionProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNrValidator $dossierNrValidator,
        private DossierSupportService $dossierSupportService,
        private DispositionRepository $dispositionRepository,
        private DispositionMapper $dispositionMapper,
        private DocumentPrefixDeterminer $documentPrefixDeterminer,
        private AttachmentSynchronizer $attachmentSynchronizer,
        private OrganisationResolver $organisationResolver,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?DispositionResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, DispositionRequestDto::class);
        Assert::string($uriVariables['dossierExternalId']);
        $dispositionExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $disposition = $this->dispositionRepository->findByOrganisationAndExternalId($organisation, $dispositionExternalId);

        if ($disposition === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNrValidator->validate($data->dossierNumber, $documentPrefix);
            $disposition = $this->create($organisation, $department, $subject, $data, $dispositionExternalId, $documentPrefix);

            return $this->dispositionMapper->fromEntity($disposition);
        }

        $this->dossierNrValidator->validate($data->dossierNumber, $disposition->getDocumentPrefix(), $disposition->getId());
        $this->update($disposition, $organisation, $department, $subject, $data);

        return $this->dispositionMapper->fromEntity($disposition);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        DispositionRequestDto $dispositionRequestDto,
        ExternalId $dispositionExternalId,
        string $documentPrefix,
    ): Disposition {
        $disposition = DispositionMapper::create(
            $dispositionRequestDto,
            $organisation,
            $department,
            $subject,
            $dispositionExternalId,
            $documentPrefix,
        );
        $mainDocument = DispositionMainDocumentMapper::create($disposition, $dispositionRequestDto->mainDocument);
        $attachments = $this->getAttachments($disposition, $dispositionRequestDto->attachments);

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);

        $disposition->setMainDocument($mainDocument);
        $this->dossierSupportService->addAttachments($disposition, $attachments);

        $this->dossierSupportService->validateDossier($disposition);
        $this->dossierSupportService->dispatchCreateDossierCommand($disposition);

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

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);

        $disposition->setMainDocument($mainDocument);
        $this->attachmentSynchronizer->sync($disposition, $dispositionRequestDto->attachments);

        $this->dossierSupportService->validateDossier($disposition);
        $this->dossierSupportService->dispatchUpdateDossierCommand($disposition);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return list<DispositionAttachment>
     */
    private function getAttachments(Disposition $disposition, array $attachments): array
    {
        return array_values(array_map(static fn (AttachmentRequestDto $attachment): DispositionAttachment => DispositionAttachmentMapper::create(
            $disposition,
            $attachment,
        ), $attachments));
    }
}
