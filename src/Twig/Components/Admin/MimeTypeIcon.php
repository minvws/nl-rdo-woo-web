<?php

declare(strict_types=1);

namespace App\Twig\Components\Admin;

use App\Domain\Upload\FileType\FileType;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class MimeTypeIcon
{
    private const string FILE_UNKNOWN = 'file-unknown';

    public ?string $class = '';
    public ?string $color = 'fill-bhr-dim-gray';
    public ?string $mimeType = null;
    public ?int $size = 24;

    public function getIconName(): string
    {
        if ($this->mimeType === null) {
            return self::FILE_UNKNOWN;
        }

        $fileType = FileType::fromMimeType($this->mimeType);
        if ($fileType === null) {
            return self::FILE_UNKNOWN;
        }

        return match ($fileType) {
            FileType::XLS => 'file-csv',
            FileType::DOC => 'file-word',
            FileType::PPT => 'file-presentation',
            FileType::TXT => 'file-text',
            FileType::ZIP => 'file-zip',
            FileType::PDF => 'file-pdf',
            FileType::AUDIO => 'file-audio',
            FileType::VIDEO => 'file-video',
            FileType::VECTOR_IMAGE => 'file-image',
        };
    }
}
