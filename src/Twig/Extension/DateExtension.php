<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DateExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_valid_date', [$this, 'isValidDate']),
        ];
    }

    /**
     * Validates whether a date string conforms to a specified format.
     */
    public function isValidDate(?string $date, string $format = 'Y-m-d'): bool
    {
        if (! $date) {
            return false;
        }

        $dt = \DateTimeImmutable::createFromFormat($format, $date);

        // DateTime::createFromFormat() returns false on failure
        // We need to ensure that it also matches exactly the given format
        if ($dt === false || $dt->format($format) !== $date) {
            return false;
        }

        return true;
    }
}
