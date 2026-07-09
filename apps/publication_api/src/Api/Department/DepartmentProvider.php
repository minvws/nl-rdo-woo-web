<?php

declare(strict_types=1);

namespace PublicationApi\Api\Department;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Api\Pagination\CursorPageFactory;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\HasId;
use Shared\Service\ApiPlatformService;
use Symfony\Component\Uid\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final readonly class DepartmentProvider implements ProviderInterface
{
    public function __construct(
        private DepartmentRepository $departmentRepository,
        private DepartmentMapper $departmentMapper,
        private CursorPageFactory $cursorPageFactory,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CursorPage|DepartmentDetailResponseDto
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($operation, $uriVariables, $context);
        }

        try {
            $departmentId = Uuid::fromString($uriVariables['departmentId']);
        } catch (InvalidArgumentException) {
            throw EntityNotFoundException::for('Department', $uriVariables['departmentId']);
        }

        return $this->provideSingle($departmentId);
    }

    /**
     * @param array<array-key, string> $uriVariables
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Operation $operation, array $uriVariables, array $context): CursorPage
    {
        $departments = $this->departmentRepository->getPaginated(
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        $mappedDtos = $this->departmentMapper->fromEntitiesWithDetail($departments);

        /** @var list<HasId> $departments */
        return $this->cursorPageFactory->create(
            $departments,
            $mappedDtos,
            $this->itemsPerPage,
            $operation,
            $uriVariables,
        );
    }

    private function provideSingle(Uuid $departmentId): DepartmentDetailResponseDto
    {
        $department = $this->departmentRepository->find($departmentId);
        if ($department === null) {
            throw EntityNotFoundException::for('Department', $departmentId);
        }

        return $this->departmentMapper->fromEntityWithDetail($department);
    }
}
