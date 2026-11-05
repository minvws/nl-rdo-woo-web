<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

final class DossierTitleTooLongException extends DossierTitleArgumentException
{
    public const string MESSAGE_KEY = 'dossier.title_too_long';

    public function __construct(public readonly int $limit)
    {
        parent::__construct('Dossier title too long');
    }

    public function getMessageKey(): string
    {
        return DossierTitleTooLongException::MESSAGE_KEY;
    }

    /**
     * @return array<string, int|string>
     */
    public function getParameters(): array
    {
        return ['{{ limit }}' => $this->limit];
    }
}
