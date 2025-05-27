<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer;

use App\Domain\WooIndex\Tooi\InformatieCategorie;
use App\Domain\WooIndex\Tooi\Ministerie;
use Carbon\CarbonInterface;
use Doctrine\Common\Collections\ArrayCollection;

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
