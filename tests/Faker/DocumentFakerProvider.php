<?php

declare(strict_types=1);

namespace Shared\Tests\Faker;

use Faker\Provider\Base;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\PublicationContext;

final class DocumentFakerProvider extends Base
{
    public function documentId(): DocumentId
    {
        return DocumentId::create((string) static::randomNumber(nbDigits: 6, strict: true));
    }

    public function publicationContext(): PublicationContext
    {
        return PublicationContext::fromString((string) static::randomNumber(nbDigits: 6, strict: true));
    }
}
