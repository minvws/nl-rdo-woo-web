<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\FacetValue;

use App\Entity\Department;

readonly class AbbreviatedValue implements FacetValueInterface
{
    public function __construct(
        private ?string $abbreviation,
        private string $value,
    ) {
    }

    public function getIndexValue(): string
    {
        return $this->abbreviation ? $this->abbreviation . '|' . $this->value : $this->value;
    }

    public function __toString(): string
    {
        return $this->abbreviation ?? $this->value;
    }

    public static function fromString(string $rawValue): self
    {
        $separatorPosition = strpos($rawValue, '|');
        if ($separatorPosition === false) {
            return new self(null, $rawValue);
        }

        return new self(
            substr($rawValue, 0, $separatorPosition),
            substr($rawValue, $separatorPosition + 1),
        );
    }

    public static function fromDepartment(Department $department): self
    {
        return new self(
            $department->getShortTag(),
            $department->getName(),
        );
    }

    public function getValue(): string
    {
        return $this->abbreviation ?? '';
    }

    public function getDescription(): string
    {
        return $this->value;
    }
}
