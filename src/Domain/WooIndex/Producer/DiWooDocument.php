<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer;

use App\Domain\WooIndex\Tooi\InformatieCategorie;
use App\Domain\WooIndex\Tooi\Ministerie;
use Carbon\CarbonInterface;

final readonly class DiWooDocument
{
    public function __construct(
        public CarbonInterface $creatiedatum,
        public Ministerie $publisher,
        public string $officieleTitel,
        public InformatieCategorie $informatieCategorie,
        public DocumentHandeling $documentHandeling,
    ) {
    }
}
