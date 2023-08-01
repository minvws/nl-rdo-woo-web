<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

class DateRangeFilter implements FilterInterface
{
    public function __construct(protected string $field, protected string $comparisonOperator)
    {
    }

    public function getQuery(array $values): ?array
    {
        if (count($values) !== 1) {
            return null;
        }

        $date = $this->asDate(array_shift($values));
        if ($date === null) {
            return null;
        }

        return [
            'range' => [
                $this->field => [
                    $this->comparisonOperator => $date->format('Y-m-d'),
                ],
            ],
        ];
    }

    protected function asDate(mixed $value): ?\DateTimeImmutable
    {
        if (! is_string($value)) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
