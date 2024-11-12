<?php

declare(strict_types=1);

namespace App\Service\Inventory\Sanitizer\DataProvider;

use App\Entity\Document;
use App\Entity\Inquiry;
use App\Entity\InquiryInventory;

readonly class InquiryInventoryDataProvider implements InventoryDataProviderInterface
{
    public function __construct(
        private Inquiry $inquiry,
        /** @var array<array-key, Document> $documents */
        private array $documents,
    ) {
    }

    /**
     * @return array<array-key, Document>
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function getInventoryEntity(): InquiryInventory
    {
        $inventory = $this->inquiry->getInventory();
        if (! $inventory) {
            $inventory = new InquiryInventory();
            $inventory->setInquiry($this->inquiry);
        }

        return $inventory;
    }

    public function getFilename(): string
    {
        return 'inventarislijst-' . $this->inquiry->getCasenr();
    }
}
