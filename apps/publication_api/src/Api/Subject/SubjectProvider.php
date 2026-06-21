<?php

declare(strict_types=1);

namespace PublicationApi\Api\Subject;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\ApiPlatformService;
use Symfony\Component\Uid\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

use function count;

final readonly class SubjectProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private SubjectRepository $subjectRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|SubjectDetailResponse
    {
        $organisation = $this->organisationResolver->resolve($uriVariables);

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        try {
            $subjectId = Uuid::fromString($uriVariables['subjectId']);
        } catch (InvalidArgumentException) {
            throw EntityNotFoundException::for('Subject', $uriVariables['subjectId']);
        }

        return $this->provideSingle($organisation, $subjectId);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Organisation $organisation, array $context): ArrayPaginator
    {
        $subjects = $this->subjectRepository->getByOrganisation(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator(SubjectMapper::fromEntitiesWithDetail($subjects), 0, count($subjects));
    }

    private function provideSingle(Organisation $organisation, Uuid $subjectId): SubjectDetailResponse
    {
        $subject = $this->subjectRepository->findByOrganisationAndId($organisation, $subjectId);
        if ($subject === null) {
            throw EntityNotFoundException::for('Subject', $subjectId);
        }

        return SubjectMapper::fromEntityWithDetail($subject);
    }
}
