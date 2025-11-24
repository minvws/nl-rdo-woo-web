<?php

declare(strict_types=1);

namespace Shared\Api\Admin\Department\RemoveLogo;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class RemoveLogoProvider implements ProviderInterface
{
    public function __construct(private DepartmentRepository $departmentRepository)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Department
    {
        unset($operation, $context);

        $departmentId = $uriVariables['departmentId'];
        Assert::isInstanceOf($departmentId, Uuid::class);

        return $this->departmentRepository->findOne($departmentId);
    }
}
