<?php

declare(strict_types=1);

namespace PublicationApi\Api\Subject;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Api\Pagination\CursorPageFactory;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\HasId;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\ApiPlatformService;
use Symfony\Component\Uid\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final readonly class SubjectProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private SubjectRepository $subjectRepository,
        private CursorPageFactory $cursorPageFactory,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CursorPage|SubjectDetailResponse
    {
        $organisation = $this->organisationResolver->resolve($uriVariables);

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $operation, $uriVariables, $context);
        }

        try {
            $subjectId = Uuid::fromString((string) $uriVariables['subjectId']);
        } catch (InvalidArgumentException) {
            throw EntityNotFoundException::for('Subject', $uriVariables['subjectId']);
        }

        return $this->provideSingle($organisation, $subjectId);
    }

    /**
     * @param array<array-key,mixed> $context
     * @param array<array-key, string> $uriVariables
     */
    private function provideCollection(
        Organisation $organisation,
        Operation $operation,
        array $uriVariables,
        array $context,
    ): CursorPage {
        $subjects = $this->subjectRepository->getByOrganisation(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        $mappedDtos = SubjectMapper::fromEntitiesWithDetail($subjects);

        /** @var list<HasId> $subjects */
        return $this->cursorPageFactory->create(
            $subjects,
            $mappedDtos,
            $this->itemsPerPage,
            $operation,
            $uriVariables,
        );
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
