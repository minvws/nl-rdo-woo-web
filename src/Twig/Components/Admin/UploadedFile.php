<?php

declare(strict_types=1);

namespace App\Twig\Components\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class UploadedFile
{
    public ?string $deleteUrl;
    public ?string $downloadUrl;
    public string $fileName;
    public ?string $fileSize;
    public ?string $mimeType;
}
