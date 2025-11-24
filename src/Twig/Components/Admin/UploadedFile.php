<?php

declare(strict_types=1);

namespace Shared\Twig\Components\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class UploadedFile
{
    public ?string $deleteUrl = null;
    public ?string $downloadUrl = null;
    public string $fileName;
    public int $fileSize = 0;
    public ?string $mimeType = null;
}
