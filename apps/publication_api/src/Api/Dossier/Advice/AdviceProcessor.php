<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use PublicationApi\Api\Dossier\DossierProcessor;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProcessor<Advice,AdviceRequestDto,AdviceResponseDto>
 */
#[Autoconfigure(bind: [
    '$dossierStrategy' => '@' . AdviceStrategy::class,
    '$dossierResponseMapper' => '@' . AdviceResponseMapper::class,
])]
final readonly class AdviceProcessor extends DossierProcessor
{
}
