<?php

declare(strict_types=1);

namespace Shared\Service\Security\Authorization;

readonly class ConfigFactory
{
    /**
     * @param array{entries?: array<array-key, mixed>} $config
     */
    public function __construct(
        private array $config,
    ) {
    }

    /**
     * @return array<array-key, Entry>
     */
    public function create(): array
    {
        $entries = [];
        foreach ($this->config['entries'] ?? [] as $data) {
            /** @var array<array-key, mixed> $data */
            $entries[] = Entry::createFrom($data);
        }

        return $entries;
    }
}
