<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Subject;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\ApiPlatformService;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class SubjectProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private SubjectRepository $subjectRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|SubjectDto|null
    {
        $organisation = $this->organisationRepository->find($uriVariables['organisationId']);
        if ($organisation === null) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        $subjectId = $uriVariables['subjectId'];
        Assert::isInstanceOf($subjectId, Uuid::class);

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

        return new ArrayPaginator(SubjectMapper::fromEntities($subjects), 0, count($subjects));
    }

    private function provideSingle(Organisation $organisation, Uuid $subjectId): ?SubjectDto
    {
        $subject = $this->subjectRepository->findByOrganisationAndId($organisation, $subjectId);
        if ($subject === null) {
            return null;
        }

        return SubjectMapper::fromEntity($subject);
    }
}
