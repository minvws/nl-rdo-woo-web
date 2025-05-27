<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer\Repository;

use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Domain\Publication\Dossier\Type\DossierType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class RawUrlDto
{
    public readonly DossierFileType $source;
    public readonly DossierType $dossierType;

    /**
     * @param DossierFileType|value-of<DossierFileType>   $source
     * @param DossierType|value-of<DossierType>           $dossierType
     * @param ?ArrayCollection<array-key,RawReferenceDto> $hasParts
     */
    public function __construct(
        DossierFileType|string $source,
        public readonly Uuid $id,
        public readonly \DateTimeInterface $documentUpdatedAt,
        public readonly \DateTimeInterface $documentDate,
        public readonly string $documentFileName,
        public readonly Uuid $dossierId,
        public readonly string $documentPrefix,
        public readonly string $dossierNr,
        DossierType|string $dossierType,
        public ?RawReferenceDto $mainDocumentReference = null,
        public ?ArrayCollection $hasParts = null,
    ) {
        $this->source = is_string($source)
            ? DossierFileType::from($source)
            : $source;

        $this->dossierType = is_string($dossierType)
            ? DossierType::from($dossierType)
            : $dossierType;
    }
}
