<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\Dossier\DossierProcessor;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @extends DossierProcessor<ComplaintJudgement,ComplaintJudgementRequestDto,ComplaintJudgementResponseDto>
 */
#[Autoconfigure(bind: [
    '$dossierStrategy' => '@' . ComplaintJudgementStrategy::class,
    '$dossierResponseMapper' => '@' . ComplaintJudgementResponseMapper::class,
])]
final readonly class ComplaintJudgementProcessor extends DossierProcessor
{
}
