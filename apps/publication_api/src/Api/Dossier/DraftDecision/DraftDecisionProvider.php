<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\Dossier\DossierProvider;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProvider<DraftDecision,DraftDecisionResponseDto>
 */
#[Autoconfigure(bind: [
    '$abstractDossierRepository' => '@' . DraftDecisionRepository::class,
    '$dossierResponseMapper' => '@' . DraftDecisionResponseMapper::class,
    '$entityName' => 'DraftDecision',
])]
final readonly class DraftDecisionProvider extends DossierProvider
{
}
