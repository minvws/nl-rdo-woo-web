<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

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
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements ProcessorInterface<CovenantRequestDto,?CovenantResponseDto>
 */
final readonly class CovenantProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNrValidator $dossierNrValidator,
        private DossierSupportService $dossierSupportService,
        private CovenantRepository $covenantRepository,
        private CovenantMapper $covenantMapper,
        private DocumentPrefixDeterminer $documentPrefixDeterminer,
        private AttachmentSynchronizer $attachmentSynchronizer,
        private OrganisationResolver $organisationResolver,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?CovenantResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, CovenantRequestDto::class);

        $covenantExternalId = $uriVariables['dossierExternalId'];
        Assert::string($covenantExternalId);
        $covenantExternalId = ExternalId::create($covenantExternalId);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $covenant = $this->covenantRepository->findByOrganisationAndExternalId($organisation, $covenantExternalId);

        if ($covenant === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNrValidator->validate($data->dossierNumber, $documentPrefix);
            $covenant = $this->create($organisation, $department, $subject, $data, $covenantExternalId, $documentPrefix);

            return $this->covenantMapper->fromEntity($covenant);
        }

        $this->dossierNrValidator->validate($data->dossierNumber, $covenant->getDocumentPrefix(), $covenant->getId());
        $this->update($covenant, $organisation, $department, $subject, $data);

        return $this->covenantMapper->fromEntity($covenant);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        CovenantRequestDto $covenantRequestDto,
        ExternalId $covenantExternalId,
        string $documentPrefix,
    ): Covenant {
        $covenant = CovenantMapper::create(
            $covenantRequestDto,
            $organisation,
            $department,
            $subject,
            $covenantExternalId,
            $documentPrefix,
        );
        $mainDocument = CovenantMainDocumentMapper::create($covenant, $covenantRequestDto->mainDocument);
        $attachments = $this->getAttachments($covenant, $covenantRequestDto->attachments);

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);

        $covenant->setMainDocument($mainDocument);
        $this->dossierSupportService->addAttachments($covenant, $attachments);

        $this->dossierSupportService->validateDossier($covenant);
        $this->dossierSupportService->dispatchCreateDossierCommand($covenant);

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

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);

        $covenant->setMainDocument($mainDocument);
        $this->attachmentSynchronizer->sync($covenant, $covenantRequestDto->attachments);

        $this->dossierSupportService->validateDossier($covenant);
        $this->dossierSupportService->dispatchUpdateDossierCommand($covenant);
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
