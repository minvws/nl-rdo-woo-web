<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\OtherPublication;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Publication\Dossier\DossierSupportService;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Document\DocumentPrefixDeterminer;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements ProcessorInterface<OtherPublicationRequestDto,?OtherPublicationResponseDto>
 */
final readonly class OtherPublicationProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierSupportService $dossierSupportService,
        private OtherPublicationRepository $otherPublicationRepository,
        private OtherPublicationMapper $otherPublicationMapper,
        private DocumentPrefixDeterminer $documentPrefixDeterminer,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?OtherPublicationResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, OtherPublicationRequestDto::class);

        $otherPublicationExternalId = $uriVariables['dossierExternalId'];
        Assert::string($otherPublicationExternalId);
        $otherPublicationExternalId = ExternalId::create($otherPublicationExternalId);

        $organisation = $this->dossierSupportService->getOrganisation($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $otherPublication = $this->otherPublicationRepository->findByOrganisationAndExternalId($organisation, $otherPublicationExternalId);

        if ($otherPublication === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $otherPublication = $this->create($organisation, $department, $subject, $data, $otherPublicationExternalId, $documentPrefix);

            return $this->otherPublicationMapper->fromEntity($otherPublication);
        }

        $this->update($otherPublication, $organisation, $department, $subject, $data);

        return $this->otherPublicationMapper->fromEntity($otherPublication);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        OtherPublicationRequestDto $otherPublicationRequestDto,
        ExternalId $otherPublicationExternalId,
        string $documentPrefix,
    ): OtherPublication {
        $otherPublication = OtherPublicationMapper::create(
            $otherPublicationRequestDto,
            $organisation,
            $department,
            $subject,
            $otherPublicationExternalId,
            $documentPrefix,
        );
        $mainDocument = OtherPublicationMainDocumentMapper::create($otherPublication, $otherPublicationRequestDto->mainDocument);
        $attachments = $this->getAttachments($otherPublication, $otherPublicationRequestDto->attachments);

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);

        $otherPublication->setMainDocument($mainDocument);
        $this->dossierSupportService->addAttachments($otherPublication, $attachments);

        $this->dossierSupportService->validateDossier($otherPublication);
        $this->dossierSupportService->dispatchCreateDossierCommand($otherPublication);

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

        $this->dossierSupportService->validateMainDocument($mainDocument);
        $this->dossierSupportService->validateAttachments($attachments);

        $otherPublication->setMainDocument($mainDocument);
        $this->dossierSupportService->removeDossierAttachments($otherPublication);
        $this->dossierSupportService->addAttachments($otherPublication, $attachments);

        $this->dossierSupportService->validateDossier($otherPublication);
        $this->dossierSupportService->dispatchUpdateDossierCommand($otherPublication);
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
