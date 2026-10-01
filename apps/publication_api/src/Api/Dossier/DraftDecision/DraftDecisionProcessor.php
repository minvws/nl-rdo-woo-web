<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\Dossier\DossierProcessor;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProcessor<DraftDecision,DraftDecisionRequestDto,DraftDecisionResponseDto>
 */
#[Autoconfigure(bind: [
    '$dossierStrategy' => '@' . DraftDecisionStrategy::class,
    '$dossierResponseMapper' => '@' . DraftDecisionResponseMapper::class,
])]
final readonly class DraftDecisionProcessor extends DossierProcessor
{
}
