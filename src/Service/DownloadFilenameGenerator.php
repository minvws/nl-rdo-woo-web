<?php

declare(strict_types=1);

namespace Shared\Service;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\EntityWithFileInfo;
use Webmozart\Assert\Assert;

use function sprintf;

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
