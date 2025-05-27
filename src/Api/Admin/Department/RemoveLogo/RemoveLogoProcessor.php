<?php

declare(strict_types=1);

namespace App\Api\Admin\Department\RemoveLogo;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Department\DepartmentFileService;
use App\Entity\Department;
use Webmozart\Assert\Assert;

final readonly class RemoveLogoProcessor implements ProcessorInterface
{
    public function __construct(private DepartmentFileService $departmentFileService)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        unset($operation, $uriVariables, $context);

        $department = $data;
        Assert::isInstanceOf($department, Department::class);

        $this->departmentFileService->removeDepartmentLogo($department);
    }
}
