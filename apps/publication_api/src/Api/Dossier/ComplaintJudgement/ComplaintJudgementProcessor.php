<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PublicationApi\Api\Dossier\DossierNrValidator;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Document\DocumentPrefixDeterminer;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<ComplaintJudgementRequestDto,?ComplaintJudgementResponseDto>
 */
final readonly class ComplaintJudgementProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNrValidator $dossierNrValidator,
        private DossierSupportService $dossierSupportService,
        private ComplaintJudgementRepository $complaintJudgementRepository,
        private ComplaintJudgementMapper $complaintJudgementMapper,
        private DocumentPrefixDeterminer $documentPrefixDeterminer,
        private OrganisationResolver $organisationResolver,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?ComplaintJudgementResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::string($uriVariables['dossierExternalId']);
        $dossierExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);
        Assert::isInstanceOf($data, ComplaintJudgementRequestDto::class);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $complaintJudgement = $this->complaintJudgementRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);

        if (! $complaintJudgement instanceof ComplaintJudgement) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNrValidator->validate($data->dossierNumber, $documentPrefix);
            $complaintJudgement = $this->create($organisation, $department, $subject, $data, $dossierExternalId, $documentPrefix);

            return $this->complaintJudgementMapper->fromEntity($complaintJudgement);
        }

        $this->dossierNrValidator->validate($data->dossierNumber, $complaintJudgement->getDocumentPrefix(), $complaintJudgement->getId());
        $this->update($complaintJudgement, $organisation, $department, $subject, $data);

        return $this->complaintJudgementMapper->fromEntity($complaintJudgement);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ComplaintJudgementRequestDto $complaintJudgementRequestDto,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): ComplaintJudgement {
        $complaintJudgement = ComplaintJudgementMapper::create(
            $complaintJudgementRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );
        $mainDocument = ComplaintJudgementMainDocumentMapper::create($complaintJudgement, $complaintJudgementRequestDto->mainDocument);

        $this->dossierSupportService->validateMainDocument($mainDocument);

        $complaintJudgement->setMainDocument($mainDocument);

        $this->dossierSupportService->validateDossier($complaintJudgement);
        $this->dossierSupportService->dispatchCreateDossierCommand($complaintJudgement);

        return $complaintJudgement;
    }

    private function update(
        ComplaintJudgement $complaintJudgement,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ComplaintJudgementRequestDto $complaintJudgementRequestDto,
    ): void {
        $complaintJudgement = ComplaintJudgementMapper::update(
            $complaintJudgement,
            $complaintJudgementRequestDto,
            $organisation,
            $department,
            $subject,
        );
        $mainDocument = ComplaintJudgementMainDocumentMapper::update($complaintJudgement, $complaintJudgementRequestDto->mainDocument);

        $this->dossierSupportService->validateMainDocument($mainDocument);

        $complaintJudgement->setMainDocument($mainDocument);

        $this->dossierSupportService->validateDossier($complaintJudgement);
        $this->dossierSupportService->dispatchUpdateDossierCommand($complaintJudgement);
    }
}
