<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Entity\Document;
use App\Entity\Inquiry;
use App\Entity\Organisation;
use App\Message\UpdateInquiryLinksMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

/**
 * This class aggregates Document level changes to caseNrs into a changeset grouped by caseNrs. Grouping it that way
 * makes it much more efficient to process, as it can be applied as one change per case. This is done asynchronously.
 */
class InquiryUpdater
{
    public const ADD = 'add';
    public const DEL = 'del';

    /**
     * @var array<string, non-empty-array<string,array<Uuid>>>
     */
    private array $changeset = [];

    public function __construct(
        private readonly Organisation $organisation,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function addToChangeset(DocumentMetadata $documentMetadata, Document $document): void
    {
        $currentCaseNumbers = $document->getInquiries()->map(
            /* @phpstan-ignore-next-line */
            fn (Inquiry $inquiry) => $inquiry->getCasenr()
        )->toArray();

        $removeCaseNrs = array_diff($currentCaseNumbers, $documentMetadata->getCaseNumbers());
        $this->addActionForCases($document, self::DEL, $removeCaseNrs);

        $addCaseNrs = array_diff($documentMetadata->getCaseNumbers(), $currentCaseNumbers);
        $this->addActionForCases($document, self::ADD, $addCaseNrs);
    }

    public function flushChangeset(): void
    {
        foreach ($this->changeset as $caseNr => $actions) {
            $this->messageBus->dispatch(
                new UpdateInquiryLinksMessage(
                    $this->organisation->getId(),
                    strval($caseNr),
                    $actions[self::ADD],
                    $actions[self::DEL]
                )
            );
        }

        $this->changeset = [];
    }

    /**
     * @param string[] $caseNrs
     */
    private function addActionForCases(Document $document, string $action, array $caseNrs): void
    {
        foreach ($caseNrs as $caseNr) {
            if (! array_key_exists($caseNr, $this->changeset)) {
                $this->changeset[$caseNr] = [
                    self::ADD => [],
                    self::DEL => [],
                ];
            }

            $this->changeset[$caseNr][$action][] = $document->getId();
        }
    }
}
