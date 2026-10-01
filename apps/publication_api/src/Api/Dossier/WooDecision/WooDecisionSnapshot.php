<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use Doctrine\Common\Collections\Collection;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inquiry\DocumentInquiryNumbers;

readonly class WooDecisionSnapshot
{
    /**
     * @param Collection<string,DocumentInquiryNumbers> $documentInquiryNumbers
     */
    private function __construct(
        public ?MetadataSnapshot $mainDocument,
        public Collection $documentInquiryNumbers,
    ) {
    }

    /**
     * @param Collection<string,DocumentInquiryNumbers> $documentInquiryNumbers
     */
    public static function of(WooDecision $wooDecision, Collection $documentInquiryNumbers): self
    {
        return new self(
            MetadataSnapshot::ofNullable($wooDecision->getMainDocument()),
            $documentInquiryNumbers,
        );
    }
}
