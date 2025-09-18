<?php

declare(strict_types=1);

namespace App\Api\OpenApi\UsageDetector;

final class UsedComponents implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @param array<value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS>,array<string,true>> $state
     */
    private function __construct(private array $state)
    {
    }

    public static function new(): self
    {
        /** @var array<value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS>,array<string,true>> $state */
        $state = array_fill_keys(OpenApiComponentsUsageDetector::COMPONENT_SECTIONS, []);

        return new self($state);
    }

    /**
     * @param value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS> $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, OpenApiComponentsUsageDetector::COMPONENT_SECTIONS, true);
    }

    /**
     * @param value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS> $offset
     *
     * @return array<string,true>
     */
    public function &offsetGet(mixed $offset): mixed
    {
        if (! $this->offsetExists($offset)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid component section.', $offset));
        }

        return $this->state[$offset];
    }

    /**
     * @param value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS> $offset
     * @param array<string,true>                                           $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (! $this->offsetExists($offset)) {
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
        $this[$offset] = [];
    }

    /**
     * @return \Traversable<value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS>,array<string,true>>
     */
    public function getIterator(): \Traversable
    {
        /** @var \Traversable<value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS>,array<string,true>> */
        return new \ArrayIterator($this->state);
    }

    /**
     * @param value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS> $section
     */
    public function mark(string $section, string $name): self
    {
        $this[$section][$name] = true;

        return $this;
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
