<?php

declare(strict_types=1);

namespace Shared\Tests\Faker;

use Faker\Provider\File;
use Shared\Domain\Upload\FileType\FileExtension;
use Shared\Service\Uploader\UploadGroupId;
use Shared\ValueObject\FileName;
use Webmozart\Assert\Assert;

use function sprintf;

final class FileFakerProvider extends File
{
    public function fileName(): FileName
    {
        $name = $this->generator->word();

        if ($this->generator->boolean(90)) {
            return FileName::create(sprintf('%s.%s', $name, static::fileExtension()));
        }

        return FileName::create($name);
    }

    public function fileNameForGroup(UploadGroupId $uploadGroupId): FileName
    {
        $name = $this->generator->word();
        $fileExtension = $this->generator->randomElement($uploadGroupId->getExtensions());
        Assert::isInstanceOf($fileExtension, FileExtension::class);

        return FileName::create(sprintf('%s.%s', $name, $fileExtension->value));
    }
}
