<?php

declare(strict_types=1);

namespace App\Service\Inventory\Sanitizer;

use App\Entity\Document;
use App\Entity\EntityWithFileInfo;
use App\Entity\Inquiry;
use App\Entity\InquiryInventory;

class InquiryInventoryDataProvider implements InventoryDataProviderInterface
{
    public function __construct(
        private readonly Inquiry $inquiry,
        /** @var Document[] $documents */
        private readonly array $documents,
    ) {
    }

    /**
     * @return Document[]
     */
    public function getDocuments(): iterable
    {
        return $this->documents;
    }

    public function getInventoryEntity(): EntityWithFileInfo
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
