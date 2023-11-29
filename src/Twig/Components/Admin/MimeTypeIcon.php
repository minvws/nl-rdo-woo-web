<?php

declare(strict_types=1);

namespace App\Twig\Components\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class MimeTypeIcon
{
    public ?string $class = '';
    public ?string $color = 'fill-dim-gray';
    public ?string $mimeType = null;
    public ?int $size = 24;

    public function getIconName(): string
    {
        $defaultIconName = 'file-unknown';
        if ($this->mimeType === null) {
            return $defaultIconName;
        }

        $mapping = [
            'file-csv' => [
                'application/vnd.ms-excel',
                'application/vnd.oasis.opendocument.spreadsheet',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/xls',
            ],
            'file-pdf' => [
                'application/pdf',
                'application/x-pdf',
            ],
            'file-word' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            'file-zip' => [
                'application/x-zip-compressed',
                'application/zip',
            ],
        ];

        foreach ($mapping as $iconName => $mimeTypes) {
            if (in_array($this->mimeType, $mimeTypes)) {
                return $iconName;
            }
        }

        return $defaultIconName;
    }
}
