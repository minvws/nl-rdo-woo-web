<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload;

use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

class BatchDownloadScope
{
    private function __construct(
        public ?WooDecision $wooDecision,
        public ?Inquiry $inquiry,
    ) {
    }

    public static function forWooDecision(WooDecision $wooDecision): self
    {
        return new self($wooDecision, null);
    }

    public static function forInquiry(Inquiry $inquiry): self
    {
        return new self(null, $inquiry);
    }

    public static function forInquiryAndWooDecision(Inquiry $inquiry, WooDecision $wooDecision): self
    {
        return new self($wooDecision, $inquiry);
    }

    public static function fromBatch(BatchDownload $batch): self
    {
        return new self($batch->getDossier(), $batch->getInquiry());
    }

    /**
     * @phpstan-assert-if-true !null $this->wooDecision
     * @phpstan-assert-if-true !null $this->inquiry
     */
    public function containsBothInquiryAndWooDecision(): bool
    {
        return $this->wooDecision !== null && $this->inquiry !== null;
    }
}
