<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Entity\Department;
use Doctrine\Common\Collections\ArrayCollection;

class DossierFilterParameters
{
    /** @var DossierStatus[] */
    public array $statuses;

    /** @var DossierType[] */
    public array $types;

    /** @var ArrayCollection<Department>|null */
    public ?ArrayCollection $departments;
}
