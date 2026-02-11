<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Department;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Service\ApiPlatformService;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|DepartmentDto|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($context);
        }

        $departmentId = $uriVariables['departmentId'];
        Assert::isInstanceOf($departmentId, Uuid::class);

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

    private function provideSingle(Uuid $departmentId): ?DepartmentDto
    {
        $department = $this->departmentRepository->find($departmentId);
        if ($department === null) {
            return null;
        }

        return DepartmentMapper::fromEntity($department);
    }
}
