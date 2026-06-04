<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Upload;

use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\ConstraintViolationList;
use Webmozart\Assert\Assert;

use function file_put_contents;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

readonly class TempFileService
{
    public function __construct(
        private Filesystem $filesystem,
    ) {
    }

    public function create(string $fileName): string
    {
        $tempPath = $this->filesystem->tempnam(sys_get_temp_dir(), 'api_upload_', sprintf('_%s', $fileName));
        Assert::string($tempPath);

        return $tempPath;
    }

    public function delete(string $tempPath): void
    {
        unlink($tempPath);
    }

    public function write(string $path, string $content): void
    {
        $uploadedBytes = file_put_contents($path, $content);

        if ($uploadedBytes === 0 || $uploadedBytes === false) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Could not write file content'));
        }
    }
}
