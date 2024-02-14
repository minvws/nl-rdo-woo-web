<?php

declare(strict_types=1);

namespace App\ViewModel;

use App\Entity\Department;
use App\Entity\Dossier as EntityDossier;
use App\Enum\Department as EnumDepartment;
use Webmozart\Assert\Assert;

final readonly class Dossier
{
    public function __construct(
        public EntityDossier $entity,
        public DossierCounts $counts,
    ) {
    }

    public function isVwsResponsible(): bool
    {
        if ($this->entity->getDepartments()->count() === 1) {
            $department = $this->entity->getDepartments()->first();

            Assert::isInstanceOf($department, Department::class);

            return EnumDepartment::VWS->equals($department->getName());
        }

        return false;
    }
}
