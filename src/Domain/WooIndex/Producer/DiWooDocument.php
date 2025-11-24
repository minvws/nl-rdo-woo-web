<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer;

use Carbon\CarbonInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Shared\Domain\WooIndex\Tooi\InformatieCategorie;
use Shared\Domain\WooIndex\Tooi\Ministerie;

final readonly class DiWooDocument
{
    /**
     * @param ?ArrayCollection<array-key,UrlReference> $hasParts
     */
    public function __construct(
        public CarbonInterface $creatiedatum,
        public Ministerie $publisher,
        public string $officieleTitel,
        public InformatieCategorie $informatieCategorie,
        public DocumentHandeling $documentHandeling,
        public ?UrlReference $isPartOf,
        public ?ArrayCollection $hasParts,
    ) {
    }
}
