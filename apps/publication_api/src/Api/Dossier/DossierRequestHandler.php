<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use PublicationApi\FeatureFlag\DossierUpdateGuard;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

readonly class DossierRequestHandler
{
    public function __construct(
        private DossierNumberValidator $dossierNumberValidator,
        private DossierRepository $dossierRepository,
        private DossierSupportService $dossierSupportService,
        private DossierUpdateGuard $dossierUpdateGuard,
    ) {
    }

    /**
     * @template TDossier of AbstractDossier
     * @template TRequestDto of DossierRequestDtoInterface
     *
     * @param TRequestDto $dossierRequestDto
     * @param DossierStrategy<TDossier, TRequestDto> $dossierStrategy
     *
     * @return TDossier
     */
    public function handle(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        ExternalId $dossierExternalId,
        DossierStrategy $dossierStrategy,
    ): AbstractDossier {
        $dossierType = $dossierStrategy->dossierType();

        $subject = $this->dossierSupportService->getSubject($dossierRequestDto, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $dossierRequestDto->departmentId);
        $dossier = $this->dossierRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);

        if ($dossier !== null && ! $dossier instanceof $dossierType) {
            throw ExternalIdInUseException::forExternalIdAlreadyUsed($dossier->getType());
        }

        $dossierStrategy->validateRequest($dossierRequestDto);

        if ($dossier === null) {
            $documentPrefix = $organisation->getPrefix()->toString();
            $this->dossierNumberValidator->validate($dossierRequestDto->dossierNumber, $documentPrefix);

            return $dossierStrategy->create(
                $dossierRequestDto,
                $organisation,
                $department,
                $subject,
                $dossierExternalId,
                $documentPrefix,
            );
        }

        Assert::isInstanceOf($dossier, $dossierType);

        $this->dossierUpdateGuard->assertDossierIsEditable($dossier);

        $this->dossierNumberValidator->validate($dossierRequestDto->dossierNumber, $dossier->getDocumentPrefix(), $dossier->getId());

        $dossierStrategy->update($dossier, $dossierRequestDto, $organisation, $department, $subject);

        return $dossier;
    }
}
