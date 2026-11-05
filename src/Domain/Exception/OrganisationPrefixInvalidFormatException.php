<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

final class OrganisationPrefixInvalidFormatException extends OrganisationPrefixArgumentException
{
    public const string MESSAGE_KEY = 'organisation.prefix_invalid_format';

    public function __construct()
    {
        parent::__construct('Organisation prefix has an invalid format');
    }

    public function getMessageKey(): string
    {
        return OrganisationPrefixInvalidFormatException::MESSAGE_KEY;
    }
}
