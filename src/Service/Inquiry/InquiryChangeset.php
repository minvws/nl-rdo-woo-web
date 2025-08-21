<?php

declare(strict_types=1);

namespace App\Service\Inquiry;

use App\Domain\Organisation\Organisation;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * This class aggregates Document level changes to caseNrs into a changeset grouped by caseNrs. Grouping it that way
 * makes it much more efficient to process, as it can be applied as one change per case.
 */
class InquiryChangeset
{
    public const string ADD_DOCUMENTS = 'add_documents';
    public const string DEL_DOCUMENTS = 'del_documents';
    public const string ADD_DOSSIERS = 'add_dossiers';

    /**
     * @var array<string, array<string, array<Uuid>>>
     */
    private array $changes = [];

    public function __construct(
        private readonly Organisation $organisation,
    ) {
    }

    public function updateCaseNrsForDocument(
        DocumentCaseNumbers $documentCaseNumbers,
        CaseNumbers $updatedCaseNumbers,
    ): void {
        $currentCaseNumbers = $documentCaseNumbers->caseNumbers;
        Assert::notNull($documentCaseNumbers->documentId);

        $caseNumbersToRemove = $updatedCaseNumbers->getMissingValuesComparedToInput($currentCaseNumbers);
        $this->addActionForCases($documentCaseNumbers->documentId, self::DEL_DOCUMENTS, $caseNumbersToRemove);

        $caseNumbersToAdd = $updatedCaseNumbers->getExtraValuesComparedToInput($currentCaseNumbers);
        $this->addActionForCases($documentCaseNumbers->documentId, self::ADD_DOCUMENTS, $caseNumbersToAdd);
    }

    public function addCaseNrsForDossier(WooDecision $dossier, CaseNumbers $updatedCaseNumbers): void
    {
        $currentCaseNumbers = CaseNumbers::forWooDecision($dossier);

        $caseNumbersToAdd = $updatedCaseNumbers->getExtraValuesComparedToInput($currentCaseNumbers);
        $this->addActionForCases($dossier->getId(), self::ADD_DOSSIERS, $caseNumbersToAdd);
    }

    private function addActionForCases(Uuid $id, string $action, CaseNumbers $caseNrs): void
    {
        foreach ($caseNrs as $caseNr) {
            if (! array_key_exists($caseNr, $this->changes)) {
                $this->changes[$caseNr] = [
                    self::ADD_DOCUMENTS => [],
                    self::DEL_DOCUMENTS => [],
                    self::ADD_DOSSIERS => [],
                ];
            }

            $this->changes[$caseNr][$action][] = $id;
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
