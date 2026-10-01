<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use PublicationApi\Api\Dossier\DossierProcessor;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProcessor<Covenant,CovenantRequestDto,CovenantResponseDto>
 */
#[Autoconfigure(bind: [
    '$dossierStrategy' => '@' . CovenantStrategy::class,
    '$dossierResponseMapper' => '@' . CovenantResponseMapper::class,
])]
final readonly class CovenantProcessor extends DossierProcessor
{
}
