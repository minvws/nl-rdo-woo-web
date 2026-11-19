<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Api\Dossier\DossierProvider;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProvider<Disposition,DispositionResponseDto>
 */
#[Autoconfigure(bind: [
    '$abstractDossierRepository' => '@' . DispositionRepository::class,
    '$dossierResponseMapper' => '@' . DispositionResponseMapper::class,
    '$entityName' => 'Disposition',
])]
final readonly class DispositionProvider extends DossierProvider
{
}
