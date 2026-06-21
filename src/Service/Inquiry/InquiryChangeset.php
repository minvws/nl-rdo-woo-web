<?php

declare(strict_types=1);

namespace Shared\Service\Inquiry;

use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

use function array_key_exists;

/**
 * This class aggregates Document level changes to inquiryNumbers into a changeset grouped by inquiryNumbers. Grouping it that way
 * makes it much more efficient to process, as it can be applied as one change per case.
 */
class InquiryChangeset
{
    public const string ADD_DOCUMENTS = 'add_documents';
    public const string DEL_DOCUMENTS = 'del_documents';
    public const string ADD_DOSSIERS = 'add_dossiers';

    /**
     * @var array<string, array<string, array<array-key, Uuid>>>
     */
    private array $changes = [];

    public function __construct(
        private readonly Organisation $organisation,
    ) {
    }

    public function updateInquiryNumbersForDocument(
        DocumentInquiryNumbers $documentInquiryNumbers,
        InquiryNumbers $updatedInquiryNumbers,
    ): void {
        $currentInquiryNumbers = $documentInquiryNumbers->inquiryNumbers;
        Assert::notNull($documentInquiryNumbers->documentId);

        $inquiryNumbersToRemove = $updatedInquiryNumbers->getMissingValuesComparedToInput($currentInquiryNumbers);
        $this->addActionForInquiries($documentInquiryNumbers->documentId, self::DEL_DOCUMENTS, $inquiryNumbersToRemove);

        $inquiryNumbersToAdd = $updatedInquiryNumbers->getExtraValuesComparedToInput($currentInquiryNumbers);
        $this->addActionForInquiries($documentInquiryNumbers->documentId, self::ADD_DOCUMENTS, $inquiryNumbersToAdd);
    }

    public function addInquiryNumbersForDossier(WooDecision $dossier, InquiryNumbers $updatedInquiryNumbers): void
    {
        $currentInquiryNumbers = InquiryNumbers::forWooDecision($dossier);

        $inquiryNumbersToAdd = $updatedInquiryNumbers->getExtraValuesComparedToInput($currentInquiryNumbers);
        $this->addActionForInquiries($dossier->getId(), self::ADD_DOSSIERS, $inquiryNumbersToAdd);
    }

    private function addActionForInquiries(Uuid $id, string $action, InquiryNumbers $inquiryNumbers): void
    {
        foreach ($inquiryNumbers as $inquiryNumber) {
            if (! array_key_exists($inquiryNumber, $this->changes)) {
                $this->changes[$inquiryNumber] = [
                    self::ADD_DOCUMENTS => [],
                    self::DEL_DOCUMENTS => [],
                    self::ADD_DOSSIERS => [],
                ];
            }

            $this->changes[$inquiryNumber][$action][] = $id;
        }
    }

    public function getOrganisation(): Organisation
    {
        return $this->organisation;
    }

    /**
     * @return array<string, array<string, array<array-key, Uuid>>>
     */
    public function getChanges(): array
    {
        return $this->changes;
    }
}
