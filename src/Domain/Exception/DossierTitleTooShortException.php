<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

final class DossierTitleTooShortException extends DossierTitleArgumentException
{
    public const string MESSAGE_KEY = 'dossier.title_too_short';

    public function __construct(public readonly int $limit)
    {
        parent::__construct('Dossier title too short');
    }

    public function getMessageKey(): string
    {
        return DossierTitleTooShortException::MESSAGE_KEY;
    }

    /**
     * @return array<string, int|string>
     */
    public function getParameters(): array
    {
        return ['{{ limit }}' => $this->limit];
    }
}
