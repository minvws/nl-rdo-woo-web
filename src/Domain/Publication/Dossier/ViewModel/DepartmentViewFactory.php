<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Doctrine\Common\Collections\Collection;
use Shared\Domain\Department\Department as DepartmentEntity;

readonly class DepartmentViewFactory
{
    /**
     * @param Collection<array-key,DepartmentEntity> $departments
     *
     * @return Collection<array-key,Department>
     */
    public function makeCollection(Collection $departments): Collection
    {
        return $departments->map(fn (DepartmentEntity $entity): Department => $this->make($entity));
    }

    public function make(DepartmentEntity $entity): Department
    {
        return new Department(
            name: $entity->getName(),
            feedbackContent: $entity->getFeedbackContent(),
            responsibilityContent: $entity->getResponsibilityContent(),
        );
    }
}
