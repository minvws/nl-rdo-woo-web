<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

interface DossierRequestDtoInterface
{
    public Uuid $departmentId { get; }
    public string $dossierNumber { get; }
    public ?Uuid $subjectId { get; }
    public string $summary { get; }
    public DossierTitle $title { get; }
}
