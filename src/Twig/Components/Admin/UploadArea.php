<?php

declare(strict_types=1);

namespace App\Twig\Components\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class UploadArea
{
    /** @var array<string> */
    public ?array $accept = [];
    public ?string $css = '';
    public ?string $id = '';
    public ?string $endpoint = '';
    public ?int $maxFileSize = null;
    public ?bool $multiple = false;
    public ?string $name = 'file';
    public ?string $tip = '';

    /**
     * @param array<string> $accept
     */
    public function mount(
        /** @var array<string> */
        ?array $accept = [],
        ?string $endpoint = '',
        ?string $id = '',
        ?int $maxFileSize = null,
    ): void {
        $this->accept = $accept;
        $this->endpoint = $endpoint;
        $this->id = $id === '' ? uniqid('upload-area-') : $id;
        $this->maxFileSize = $maxFileSize;
    }

    #[ExposeInTemplate]
    public function getIsAutoUploadEnabled(): bool
    {
        return $this->endpoint !== '';
    }

    #[ExposeInTemplate]
    public function getHasAllowedMimeTypes(): bool
    {
        return $this->accept ? (count($this->accept) > 0) : false;
    }

    #[ExposeInTemplate]
    public function getHasMaxFileSize(): bool
    {
        return $this->maxFileSize !== null;
    }

    #[ExposeInTemplate]
    public function getValidExtensionsAnd(): string
    {
        return $this->getFormattedValidExtensions('en');
    }

    #[ExposeInTemplate]
    public function getValidExtensionsOr(): string
    {
        return $this->getFormattedValidExtensions('of');
    }

    #[ExposeInTemplate]
    public function getFilesLimitationsElementId(): string
    {
        return $this->appendIdWith('files-limitations');
    }

    #[ExposeInTemplate]
    public function getSelectFilesElementId(): string
    {
        return $this->appendIdWith('select-files');
    }

    #[ExposeInTemplate]
    public function getTipElementId(): string
    {
        return $this->appendIdWith('tip');
    }

    protected function appendIdWith(string $suffix): string
    {
        return $this->id . "-$suffix";
    }

    protected function getFormattedValidExtensions(string $glue): string
    {
        $extensions = $this->getValidExtensions();
        $count = count($extensions);

        if ($count === 0) {
            return '';
        } elseif ($count === 1) {
            return $extensions[0];
        }

        $lastExtension = array_pop($extensions);

        return implode(', ', $extensions) . ' ' . $glue . ' ' . $lastExtension;
    }

    /** @return array<string> */
    protected function getValidExtensions(): array
    {
        if ($this->accept === null) {
            return [];
        }

        $mimeTypeToExtension = [
            'application/pdf' => '.pdf',
            'application/vnd.oasis.opendocument.spreadsheet' => '.ods',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
            'application/vnd.ms-excel' => '.xls',
            'application/x-7z-compressed' => '.7z',
            'application/x-pdf' => '.pdf',
            'application/x-zip-compressed' => '.zip',
            'application/xls' => '.xls',
            'application/zip' => '.zip',
        ];

        /** @var array<string> */
        $extensions = [];

        foreach ($this->accept as $mimeType) {
            $extension = isset($mimeTypeToExtension[$mimeType]) ? $mimeTypeToExtension[$mimeType] : null;
            if ($extension !== null) {
                $extensions[] = $extension;
            }
        }

        $extensions = array_unique($extensions);
        sort($extensions);

        return $extensions;
    }
}
