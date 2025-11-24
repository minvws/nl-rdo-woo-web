<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierType;

class DossierFilterParameters
{
    /** @var DossierStatus[] */
    public array $statuses = [];

    /** @var DossierType[] */
    public array $types = [];

    /** @var ArrayCollection<Department>|null */
    public ?ArrayCollection $departments = null;
}
