<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\EntityWithFileInfo;
use Webmozart\Assert\Assert;

readonly class DownloadFilenameGenerator
{
    public function getFileName(EntityWithFileInfo $entity): string
    {
        if ($entity instanceof Document) {
            return sprintf(
                '%s.%s',
                $entity->getDocumentNr(),
                $entity->getFileInfo()->getType(),
            );
        }

        $filename = $entity->getFileInfo()->getName();
        Assert::string($filename);

        $sanitizer = new FilenameSanitizer($filename);
        $sanitizer->stripAdditionalCharacters();
        $sanitizer->stripIllegalFilesystemCharacters();
        $sanitizer->stripRiskyCharacters();

        return $sanitizer->getFilename();
    }
}
