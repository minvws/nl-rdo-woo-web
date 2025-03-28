<?php

declare(strict_types=1);

namespace App\Domain\Upload\Postprocessor;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Upload\UploadedFile;
use Symfony\Component\Uid\Uuid;

class NoMatchingFilePostprocessorException extends \RuntimeException
{
    public function __construct(public readonly string $fileName, public readonly Uuid $dossierId)
    {
        parent::__construct(
            sprintf(
                'No matching file processor found for file "%s" of dossier "%s"',
                $fileName,
                $dossierId,
            ),
        );
    }

    public static function create(UploadedFile $file, WooDecision $dossier): self
    {
        return new self(
            $file->getOriginalFilename(),
            $dossier->getId(),
        );
    }
}
