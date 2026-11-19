<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use PublicationApi\Api\Dossier\DossierProvider;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProvider<Advice,AdviceResponseDto>
 */
#[Autoconfigure(bind: [
    '$abstractDossierRepository' => '@' . AdviceRepository::class,
    '$dossierResponseMapper' => '@' . AdviceResponseMapper::class,
    '$entityName' => 'Advice',
])]
final readonly class AdviceProvider extends DossierProvider
{
}
