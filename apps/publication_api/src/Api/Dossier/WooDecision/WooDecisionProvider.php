<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use PublicationApi\Api\Dossier\DossierProvider;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProvider<WooDecision,WooDecisionResponseDto>
 */
#[Autoconfigure(bind: [
    '$abstractDossierRepository' => '@' . WooDecisionRepository::class,
    '$dossierResponseMapper' => '@' . WooDecisionResponseMapper::class,
    '$entityName' => 'WooDecision',
])]
final readonly class WooDecisionProvider extends DossierProvider
{
}
