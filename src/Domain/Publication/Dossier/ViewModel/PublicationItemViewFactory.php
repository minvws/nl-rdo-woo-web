<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\ViewModel;

use App\Entity\PublicationItem as PublicationItemEntity;
use Webmozart\Assert\Assert;

readonly class PublicationItemViewFactory
{
    public function make(PublicationItemEntity $publicationItem): PublicationItem
    {
        $fileName = $publicationItem->getFileInfo()->getName();
        Assert::notNull($fileName);

        return new PublicationItem(
            fileName: $fileName,
            fileSize: $publicationItem->getFileInfo()->getSize(),
            isUploaded: $publicationItem->getFileInfo()->isUploaded(),
        );
    }
}
