<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use PublicationApi\Api\Dossier\DossierProcessor;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProcessor<RequestForAdvice,RequestForAdviceRequestDto,RequestForAdviceResponseDto>
 */
#[Autoconfigure(bind: [
    '$dossierStrategy' => '@' . RequestForAdviceStrategy::class,
    '$dossierResponseMapper' => '@' . RequestForAdviceResponseMapper::class,
])]
final readonly class RequestForAdviceProcessor extends DossierProcessor
{
}
