<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Command;

use Symfony\Component\Uid\Uuid;

class UpdateInquiryLinksCommand
{
    public function __construct(
        private readonly Uuid $organisationId,
        private readonly string $caseNr,
        /** @var Uuid[] */
        private readonly array $docIdsToAdd,
        /** @var Uuid[] */
        private readonly array $docIdsToDelete,
        /** @var Uuid[] */
        private readonly array $dossierIdsToAdd,
    ) {
    }

    public function getOrganisationId(): Uuid
    {
        return $this->organisationId;
    }

    public function getCaseNr(): string
    {
        return $this->caseNr;
    }

    /**
     * @return Uuid[]
     */
    public function getDocIdsToAdd(): array
    {
        return $this->docIdsToAdd;
    }

    /**
     * @return Uuid[]
     */
    public function getDocIdsToDelete(): array
    {
        return $this->docIdsToDelete;
    }

    /**
     * @return Uuid[]
     */
    public function getDossierIdsToAdd(): array
    {
        return $this->dossierIdsToAdd;
    }
}
