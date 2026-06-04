<?php

declare(strict_types=1);

namespace Shared\Domain\Upload;

use Psr\Http\Message\StreamInterface;
use Shared\Service\Uploader\UploadGroupId;
use Stringable;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Uid\UuidV6;
use Webmozart\Assert\Assert;

use function is_array;
use function pathinfo;

use const PATHINFO_EXTENSION;

final readonly class StreamUpload
{
    public string $uploadId;
    public string $fileName;
    public InputBag $additionalParameters;

    /**
     * @param array<string,string|int|float|bool|null>|InputBag<string|int|float|bool|null> $additionalParameters
     */
    public function __construct(
        string|Stringable $fileName,
        public StreamInterface $stream,
        public UploadGroupId $groupId,
        InputBag|array $additionalParameters = new InputBag(),
        ?string $uploadId = null,
    ) {
        $this->fileName = $this->setFileName($fileName);
        $this->additionalParameters = $this->setAdditionalParameters($additionalParameters);
        $this->uploadId = $this->setUploadId($uploadId);
    }

    private function setFileName(string|Stringable $value): string
    {
        $fileName = (string) $value;

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        Assert::stringNotEmpty($extension, 'fileName must have an extension');

        return $fileName;
    }

    /**
     * @param array<string,string|int|float|bool|null>|InputBag<string|int|float|bool|null> $value
     *
     * @return InputBag<string|int|float|bool|null>
     */
    private function setAdditionalParameters(InputBag|array $value): InputBag
    {
        return match (true) {
            is_array($value) => new InputBag($value),
            default => $value,
        };
    }

    private function setUploadId(?string $value): string
    {
        return $value ?? new UuidV6()->toRfc4122();
    }
}
