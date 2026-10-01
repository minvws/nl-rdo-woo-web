<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use PublicationApi\Api\Dossier\DossierProcessor;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProcessor<OtherPublication,OtherPublicationRequestDto,OtherPublicationResponseDto>
 */
#[Autoconfigure(bind: [
    '$dossierStrategy' => '@' . OtherPublicationStrategy::class,
    '$dossierResponseMapper' => '@' . OtherPublicationResponseMapper::class,
])]
final readonly class OtherPublicationProcessor extends DossierProcessor
{
}
