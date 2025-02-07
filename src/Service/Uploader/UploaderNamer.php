<?php

declare(strict_types=1);

namespace App\Service\Uploader;

use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\File\FilesystemFile;
use Oneup\UploaderBundle\Uploader\Naming\NamerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\Assert\Assert;

readonly class UploaderNamer implements NamerInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function name(FileInterface $file): string
    {
        /** @var FilesystemFile $file */
        Assert::isInstanceOf($file, FilesystemFile::class);

        $clientOriginalName = basename($file->getClientOriginalName());

        return sprintf('%s/%s_%s', $this->getGroupId()->value, $this->getUniqid(), $clientOriginalName);
    }

    private function getGroupId(): UploadGroupId
    {
        $groupId = $this->requestStack->getCurrentRequest()?->get('groupId');

        Assert::string($groupId);

        return UploadGroupId::from($groupId);
    }

    public static function getOriginalName(string $fileName): string
    {
        return substr(
            $fileName,
            strpos($fileName, '_') + 1
        );
    }

    protected function getUniqid(): string
    {
        return uniqid(more_entropy: true);
    }
}
