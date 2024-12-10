<?php

declare(strict_types=1);

namespace App\Domain\Upload\FileType;

use App\Domain\Upload\UploadedFile;
use Symfony\Component\Mime\MimeTypesInterface;
use Webmozart\Assert\Assert;

readonly class FileTypeHelper
{
    public function __construct(
        private MimeTypesInterface $mimeTypes,
    ) {
    }

    public function fileOfType(UploadedFile $file, FileType ...$types): bool
    {
        return $this->pathnameOfType($file->getPathname(), ...$types);
    }

    public function pathnameOfType(string $path, FileType ...$types): bool
    {
        Assert::notEmpty($types, 'At least one FileType must be provided');

        $allAllowedMimeTypes = $this->getAllMimeTypes(...$types);

        $mimeType = $this->mimeTypes->guessMimeType($path);
        if ($mimeType === null) {
            return false;
        }

        return in_array($mimeType, $allAllowedMimeTypes, true);
    }

    /**
     * @return list<string>
     */
    private function getAllMimeTypes(FileType ...$types): array
    {
        /** @var array<int,string> $mimeTypes */
        $mimeTypes = array_reduce(
            $types,
            static fn (array $carry, FileType $type): array => array_merge($carry, $type->getMimeTypes()),
            [],
        );

        return array_values(array_unique($mimeTypes));
    }
}
