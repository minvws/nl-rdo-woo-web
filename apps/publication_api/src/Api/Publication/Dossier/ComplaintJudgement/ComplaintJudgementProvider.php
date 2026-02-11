<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\ComplaintJudgement;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementRepository;
use Shared\Service\ApiPlatformService;

use function count;

final readonly class ComplaintJudgementProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private ComplaintJudgementRepository $complaintJudgementRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|ComplaintJudgementDto|null
    {
        $organisation = $this->organisationRepository->find($uriVariables['organisationId']);
        if (! $organisation instanceof Organisation) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        $complaintJudgementExternalId = $uriVariables['complaintJudgementExternalId'];

        return $this->provideSingle($organisation, $complaintJudgementExternalId);
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

        return new ArrayPaginator(ComplaintJudgementMapper::fromEntities($complaintJudgements), 0, count($complaintJudgements));
    }

    private function provideSingle(Organisation $organisation, string $complaintJudgementExternalId): ?ComplaintJudgementDto
    {
        $complaintJudgement = $this->complaintJudgementRepository->findByOrganisationAndExternalId($organisation, $complaintJudgementExternalId);
        if (! $complaintJudgement instanceof ComplaintJudgement) {
            return null;
        }

        return ComplaintJudgementMapper::fromEntity($complaintJudgement);
    }
}
