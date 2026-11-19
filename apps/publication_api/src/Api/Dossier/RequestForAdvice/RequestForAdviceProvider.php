<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use PublicationApi\Api\Dossier\DossierProvider;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProvider<RequestForAdvice,RequestForAdviceResponseDto>
 */
#[Autoconfigure(bind: [
    '$abstractDossierRepository' => '@' . RequestForAdviceRepository::class,
    '$dossierResponseMapper' => '@' . RequestForAdviceResponseMapper::class,
    '$entityName' => 'RequestForAdvice',
])]
final readonly class RequestForAdviceProvider extends DossierProvider
{
}
