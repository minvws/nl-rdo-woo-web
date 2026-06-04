<?php

declare(strict_types=1);

namespace Shared\Tests\Faker;

use Faker\Provider\Uuid;
use Shared\ValueObject\ExternalId;

final class UuidFakerProvider extends Uuid
{
    public function externalId(): ExternalId
    {
        return ExternalId::create(static::uuid());
    }
}
