<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

final class DateCannotBeParsedException extends ImportException
{
    public const string MESSAGE_KEY = 'date {date} cannot be parsed';

    public function __construct(public readonly string $date)
    {
        parent::__construct('Date cannot be parsed');
    }

    public function getMessageKey(): string
    {
        return DateCannotBeParsedException::MESSAGE_KEY;
    }

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [
            '{date}' => $this->date,
        ];
    }
}
