<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer\Repository;

use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

final readonly class WooDecisionDto
{
    public function __construct(
        public Uuid $id,
        public string $documentPrefix,
        public string $dossierNr,
        public PlainDate $publicationDate,
        public ?RawReferenceDto $mainDocumentReference = null,
    ) {
    }
}
