<?php

declare(strict_types=1);

namespace App\Api\OpenApi\UsageDetector;

final class UsageQueue implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @param array<value-of<self::COMPONENT_SECTIONS>,list<string>> $state
     */
    private function __construct(private array $state)
    {
    }

    public static function new(UsedComponents $used): self
    {
        $state = [];
        foreach (OpenApiComponentsUsageDetector::COMPONENT_SECTIONS as $section) {
            $values = array_keys($used[$section]);
            if ($values !== []) {
                $state[$section] = array_keys($used[$section]);
            }
        }

        return new self($state);
    }

    /**
     * @param value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS> $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->state[$offset]);
    }

    /**
     * @param value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS> $offset
     *
     * @return list<string>
     */
    public function &offsetGet(mixed $offset): mixed
    {
        if (! $this->offsetExists($offset)) {
            throw new \InvalidArgumentException(sprintf('"%s" has no value.', $offset));
        }

        return $this->state[$offset];
    }

    /**
     * @param value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS> $offset
     * @param list<string>                                                 $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (! $this->offsetExists($offset) && ! in_array($offset, OpenApiComponentsUsageDetector::COMPONENT_SECTIONS, true)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid component section.', $offset));
        }

        if (! is_array($value)) {
            throw new \InvalidArgumentException(sprintf('Value for "%s" must be an array.', $offset));
        }

        $this->state[$offset] = $value;
    }

    /**
     * @param value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS> $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        if (! $this->offsetExists($offset)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid component section.', $offset));
        }

        unset($this->state[$offset]);
    }

    /**
     * @return \Traversable<value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS>,list<string>>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->state);
    }

    /**
     * @param value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS> $section
     */
    public function add(string $section, string $name): void
    {
        if (isset($this->state[$section]) && in_array($name, $this->state[$section], true)) {
            return;
        }

        if (! isset($this->state[$section]) && in_array($section, OpenApiComponentsUsageDetector::COMPONENT_SECTIONS, true)) {
            $this->state[$section] = [];
        }

        $this[$section][] = $name;
    }

    public function pop(string $section): ?string
    {
        if (! isset($this->state[$section]) && in_array($section, OpenApiComponentsUsageDetector::COMPONENT_SECTIONS, true)) {
            return null;
        }

        $value = array_pop($this[$section]);

        if ($this[$section] === []) {
            unset($this[$section]);
        }

        return $value;
    }

    public function hasItems(): bool
    {
        foreach ($this as $innerArray) {
            if ($innerArray !== []) {
                return true;
            }
        }

        return false;
    }
}
