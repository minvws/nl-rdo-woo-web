<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer\Repository;

use Symfony\Component\Uid\Uuid;

final readonly class WooDecisionDto
{
    public function __construct(
        public Uuid $id,
        public string $documentPrefix,
        public string $dossierNr,
        public \DateTimeInterface $publicationDate,
        public ?RawReferenceDto $mainDocumentReference = null,
    ) {
    }
}
