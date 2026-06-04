<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

use function count;

final readonly class ComplaintJudgementProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private ComplaintJudgementRepository $complaintJudgementRepository,
        private ComplaintJudgementMapper $complaintJudgementMapper,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ArrayPaginator|ComplaintJudgementResponseDto {
        $organisation = $this->organisationResolver->resolve($uriVariables);

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        return $this->provideSingle($organisation, ExternalId::create($uriVariables['dossierExternalId']));
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Organisation $organisation, array $context): ArrayPaginator
    {
        $complaintJudgements = $this->complaintJudgementRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator($this->complaintJudgementMapper->fromEntities($complaintJudgements), 0, count($complaintJudgements));
    }

    private function provideSingle(Organisation $organisation, ExternalId $dossierExternalId): ComplaintJudgementResponseDto
    {
        $complaintJudgement = $this->complaintJudgementRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);
        if ($complaintJudgement === null) {
            throw EntityNotFoundException::for('ComplaintJudgement', $dossierExternalId);
        }

        return $this->complaintJudgementMapper->fromEntity($complaintJudgement);
    }
}
