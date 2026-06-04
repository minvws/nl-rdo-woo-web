<?php

declare(strict_types=1);

namespace PublicationApi\Api\Department;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Service\ApiPlatformService;
use Symfony\Component\Uid\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

use function count;

final readonly class DepartmentProvider implements ProviderInterface
{
    public function __construct(
        private DepartmentRepository $departmentRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|DepartmentResponseDto
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($context);
        }

        try {
            $departmentId = Uuid::fromString($uriVariables['departmentId']);
        } catch (InvalidArgumentException) {
            throw EntityNotFoundException::for('Department', $uriVariables['departmentId']);
        }

        return $this->provideSingle($departmentId);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(array $context): ArrayPaginator
    {
        $departments = $this->departmentRepository->getPaginated(
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator(DepartmentMapper::fromEntities($departments), 0, count($departments));
    }

    private function provideSingle(Uuid $departmentId): DepartmentResponseDto
    {
        $department = $this->departmentRepository->find($departmentId);
        if ($department === null) {
            throw EntityNotFoundException::for('Department', $departmentId);
        }

        return DepartmentMapper::fromEntity($department);
    }
}
