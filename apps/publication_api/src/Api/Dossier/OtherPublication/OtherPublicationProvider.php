<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use PublicationApi\Api\Dossier\DossierProvider;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProvider<OtherPublication,OtherPublicationResponseDto>
 */
#[Autoconfigure(bind: [
    '$abstractDossierRepository' => '@' . OtherPublicationRepository::class,
    '$dossierResponseMapper' => '@' . OtherPublicationResponseMapper::class,
    '$entityName' => 'OtherPublication',
])]
final readonly class OtherPublicationProvider extends DossierProvider
{
}
