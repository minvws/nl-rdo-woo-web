<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use PublicationApi\Api\Dossier\DossierProcessor;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProcessor<WooDecision,WooDecisionRequestDto,WooDecisionResponseDto>
 */
#[Autoconfigure(bind: [
    '$dossierStrategy' => '@' . WooDecisionStrategy::class,
    '$dossierResponseMapper' => '@' . WooDecisionResponseMapper::class,
])]
final readonly class WooDecisionProcessor extends DossierProcessor
{
}
