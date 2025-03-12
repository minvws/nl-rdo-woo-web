<?php

declare(strict_types=1);

namespace App\Service\Inquiry;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Organisation;
use Symfony\Component\Uid\Uuid;

/**
 * This class aggregates Document level changes to caseNrs into a changeset grouped by caseNrs. Grouping it that way
 * makes it much more efficient to process, as it can be applied as one change per case.
 */
class InquiryChangeset
{
    public const ADD_DOCUMENTS = 'add_documents';
    public const DEL_DOCUMENTS = 'del_documents';
    public const ADD_DOSSIERS = 'add_dossiers';

    /**
     * @var array<string, array<string, array<Uuid>>>
     */
    private array $changes = [];

    public function __construct(
        private readonly Organisation $organisation,
    ) {
    }

    /**
     * @param string[] $updatedCaseNrs
     */
    public function updateCaseNrsForDocument(Document $document, array $updatedCaseNrs): void
    {
        $currentCaseNumbers = $document->getInquiries()->map(
            fn (Inquiry $inquiry) => $inquiry->getCasenr()
        )->toArray();

        $removeCaseNrs = array_diff($currentCaseNumbers, $updatedCaseNrs);
        $this->addActionForCases($document, self::DEL_DOCUMENTS, $removeCaseNrs);

        $addCaseNrs = array_diff($updatedCaseNrs, $currentCaseNumbers);
        $this->addActionForCases($document, self::ADD_DOCUMENTS, $addCaseNrs);
    }

    /**
     * @param string[] $updatedCaseNrs
     */
    public function addCaseNrsForDossier(WooDecision $dossier, array $updatedCaseNrs): void
    {
        $currentCaseNumbers = $dossier->getInquiries()->map(
            fn (Inquiry $inquiry) => $inquiry->getCasenr()
        )->toArray();

        $addCaseNrs = array_diff($updatedCaseNrs, $currentCaseNumbers);
        $this->addActionForCases($dossier, self::ADD_DOSSIERS, $addCaseNrs);
    }

    /**
     * @param string[] $caseNrs
     */
    private function addActionForCases(Document|WooDecision $entity, string $action, array $caseNrs): void
    {
        foreach ($caseNrs as $caseNr) {
            if (! array_key_exists($caseNr, $this->changes)) {
                $this->changes[$caseNr] = [
                    self::ADD_DOCUMENTS => [],
                    self::DEL_DOCUMENTS => [],
                    self::ADD_DOSSIERS => [],
                ];
            }

            $this->changes[$caseNr][$action][] = $entity->getId();
        }
    }

    public function getOrganisation(): Organisation
    {
        return $this->organisation;
    }

    /**
     * @return array<string, array<string, array<Uuid>>>
     */
    public function getChanges(): array
    {
        return $this->changes;
    }
}
