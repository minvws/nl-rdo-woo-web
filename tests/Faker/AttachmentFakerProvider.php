<?php

declare(strict_types=1);

namespace Shared\Tests\Faker;

use Faker\Provider\Base;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Webmozart\Assert\Assert;

final class AttachmentFakerProvider extends Base
{
    public function attachmentLanguage(): AttachmentLanguage
    {
        $attachmentLanguage = static::randomElement(AttachmentLanguage::cases());
        Assert::isInstanceOf($attachmentLanguage, AttachmentLanguage::class);

        return $attachmentLanguage;
    }

    public function attachmentType(): AttachmentType
    {
        $attachmentType = static::randomElement(AttachmentType::cases());
        Assert::isInstanceOf($attachmentType, AttachmentType::class);

        return $attachmentType;
    }
}
