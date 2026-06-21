<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Command;

use Symfony\Component\Uid\Uuid;

class UpdateInquiryLinksCommand
{
    public function __construct(
        private readonly Uuid $organisationId,
        private readonly string $inquiryNumber,
        /** @var array<Uuid> */
        private readonly array $docIdsToAdd,
        /** @var array<Uuid> */
        private readonly array $docIdsToDelete,
        /** @var array<Uuid> */
        private readonly array $dossierIdsToAdd,
    ) {
    }

    public function getOrganisationId(): Uuid
    {
        return $this->organisationId;
    }

    public function getInquiryNumber(): string
    {
        return $this->inquiryNumber;
    }

    /**
     * @return array<array-key, Uuid>
     */
    public function getDocIdsToAdd(): array
    {
        return $this->docIdsToAdd;
    }

    /**
     * @return array<array-key, Uuid>
     */
    public function getDocIdsToDelete(): array
    {
        return $this->docIdsToDelete;
    }

    /**
     * @return array<array-key, Uuid>
     */
    public function getDossierIdsToAdd(): array
    {
        return $this->dossierIdsToAdd;
    }
}
