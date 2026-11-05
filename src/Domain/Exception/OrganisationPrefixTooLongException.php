<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

final class OrganisationPrefixTooLongException extends OrganisationPrefixArgumentException
{
    public const string MESSAGE_KEY = 'organisation.prefix_too_long';

    public function __construct(public readonly int $limit)
    {
        parent::__construct('Organisation prefix too long');
    }

    public function getMessageKey(): string
    {
        return OrganisationPrefixTooLongException::MESSAGE_KEY;
    }

    /**
     * @return array<string, int|string>
     */
    public function getParameters(): array
    {
        return ['{{ limit }}' => $this->limit];
    }
}
