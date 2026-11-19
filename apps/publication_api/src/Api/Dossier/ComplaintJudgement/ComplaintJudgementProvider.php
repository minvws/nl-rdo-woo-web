<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\Dossier\DossierProvider;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProvider<ComplaintJudgement,ComplaintJudgementResponseDto>
 */
#[Autoconfigure(bind: [
    '$abstractDossierRepository' => '@' . ComplaintJudgementRepository::class,
    '$dossierResponseMapper' => '@' . ComplaintJudgementResponseMapper::class,
    '$entityName' => 'ComplaintJudgement',
])]
final readonly class ComplaintJudgementProvider extends DossierProvider
{
}
