<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

final class OrganisationPrefixTooShortException extends OrganisationPrefixArgumentException
{
    public const string MESSAGE_KEY = 'organisation.prefix_too_short';

    public function __construct(public readonly int $limit)
    {
        parent::__construct('Organisation prefix too short');
    }

    public function getMessageKey(): string
    {
        return OrganisationPrefixTooShortException::MESSAGE_KEY;
    }

    /**
     * @return array<string, int|string>
     */
    public function getParameters(): array
    {
        return ['{{ limit }}' => $this->limit];
    }
}
