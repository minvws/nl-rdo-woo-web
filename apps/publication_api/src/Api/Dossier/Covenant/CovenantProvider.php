<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use PublicationApi\Api\Dossier\DossierProvider;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProvider<Covenant,CovenantResponseDto>
 */
#[Autoconfigure(bind: [
    '$abstractDossierRepository' => '@' . CovenantRepository::class,
    '$dossierResponseMapper' => '@' . CovenantResponseMapper::class,
    '$entityName' => 'Covenant',
])]
final readonly class CovenantProvider extends DossierProvider
{
}
