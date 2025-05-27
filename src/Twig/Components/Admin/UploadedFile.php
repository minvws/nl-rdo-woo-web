<?php

declare(strict_types=1);

namespace App\Twig\Components\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class UploadedFile
{
    public ?string $deleteUrl = null;
    public ?string $downloadUrl = null;
    public string $fileName;
    public ?string $fileSize = null;
    public ?string $mimeType = null;
}
