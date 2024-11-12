<?php

declare(strict_types=1);

namespace App\Domain\Department\ViewModel;

use App\Entity\Department as DepartmentEntity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class DepartmentViewFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function make(DepartmentEntity $department): Department
    {
        return new Department(
            name: $department->getName(),
            tag: $department->getShortTag(),
            url: $this->urlGenerator->generate('app_department_detail', ['slug' => $department->getSlug()]),
        );
    }

    /**
     * @param array<array-key,DepartmentEntity> $departments
     *
     * @return list<Department>
     */
    public function makeCollection(array $departments): array
    {
        /** @var list<Department> */
        return array_map(
            fn (DepartmentEntity $department): Department => $this->make($department),
            $departments,
        );
    }
}
