<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Uploads\Document;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class WooDecisionDocumentWithdrawRequestDto
{
    public function __construct(
        public DocumentWithdrawReason $reason,
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 1000)]
        public string $explanation,
    ) {
    }
}
