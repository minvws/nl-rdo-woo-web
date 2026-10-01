<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Uploads\Document;

use ApiPlatform\Metadata\ApiProperty;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class WooDecisionDocumentWithdrawRequestDto
{
    public function __construct(
        #[ApiProperty(description: 'The reason for withdrawing the document.')]
        public DocumentWithdrawReason $reason,
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 1000)]
        #[ApiProperty(description: 'An explanation for the withdrawal (required, maximum 1000 characters).')]
        public string $explanation,
    ) {
    }
}
