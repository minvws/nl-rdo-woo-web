<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Api\Dossier\DossierProcessor;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProcessor<Disposition,DispositionRequestDto,DispositionResponseDto>
 */
#[Autoconfigure(bind: [
    '$dossierStrategy' => '@' . DispositionStrategy::class,
    '$dossierResponseMapper' => '@' . DispositionResponseMapper::class,
])]
final readonly class DispositionProcessor extends DossierProcessor
{
}
