<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use DateMalformedStringException;
use DateTimeImmutable;
use Stringable;

use function sprintf;

final readonly class PlainDate implements Stringable
{
    public const string DEFAULT_STRING_FORMAT = 'Y-m-d';

    private DateTimeImmutable $date;

    private function __construct(
        DateTimeImmutable $date,
    ) {
        $this->date = $date->setTime(0, 0);
    }

    public function __toString(): string
    {
        return $this->format(self::DEFAULT_STRING_FORMAT);
    }

    public static function today(): self
    {
        return new self(new DateTimeImmutable('today'));
    }

    public static function create(string $date): self
    {
        return self::createFromFormat(self::DEFAULT_STRING_FORMAT, $date);
    }

    public static function createFromFormat(string $format, string $date): self
    {
        $result = DateTimeImmutable::createFromFormat($format, $date);

        if ($result === false) {
            throw new PlainDateException('Unable to create PlainDate from format');
        }

        return new self($result);
    }

    public function format(string $format): string
    {
        return $this->date->format($format);
    }

    public function equalTo(PlainDate $date): bool
    {
        return $this->format(self::DEFAULT_STRING_FORMAT) === $date->format(self::DEFAULT_STRING_FORMAT);
    }

    public function toString(): string
    {
        return $this->__toString();
    }

    public function isBefore(PlainDate $date): bool
    {
        return $this->format(self::DEFAULT_STRING_FORMAT) < $date->format(self::DEFAULT_STRING_FORMAT);
    }

    public function isAfter(PlainDate $date): bool
    {
        return $this->format(self::DEFAULT_STRING_FORMAT) > $date->format(self::DEFAULT_STRING_FORMAT);
    }

    public function isToday(): bool
    {
        return $this->equalTo(self::today());
    }

    public function isPast(): bool
    {
        return $this->isBefore(self::today());
    }

    public function isFuture(): bool
    {
        return $this->isAfter(self::today());
    }

    public function addDays(int $value): self
    {
        return $this->modify(sprintf('+%d days', $value));
    }

    public function subDays(int $value): self
    {
        return $this->modify(sprintf('-%d days', $value));
    }

    public function addMonths(int $value): self
    {
        return $this->modify(sprintf('+%d months', $value));
    }

    public function subMonths(int $value): self
    {
        return $this->modify(sprintf('-%d months', $value));
    }

    public function addYears(int $value): self
    {
        return $this->modify(sprintf('+%d years', $value));
    }

    public function subYears(int $value): self
    {
        return $this->modify(sprintf('-%d years', $value));
    }

    public function firstOfMonth(): self
    {
        return $this->modify('first day of this month');
    }

    public function lastOfMonth(): self
    {
        return $this->modify('last day of this month');
    }

    public function firstOfYear(): self
    {
        return $this->modify('first day of january this year');
    }

    public function lastOfYear(): self
    {
        return $this->modify('last day of december this year');
    }

    private function modify(string $modifier): self
    {
        try {
            return new self($this->date->modify($modifier));
        } catch (DateMalformedStringException $previous) {
            throw new PlainDateException($previous->getMessage(), $previous->getCode(), $previous);
        }
    }
}
