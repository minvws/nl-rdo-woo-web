<?php

declare(strict_types=1);

namespace App\Service\Security\Authorization;

class ConfigFactory
{
    /**
     * @param array{entries: mixed[]} $config
     */
    public function __construct(
        private readonly array $config,
    ) {
    }

    /**
     * @return Entry[]
     */
    public function create(): array
    {
        $entries = [];
        foreach ($this->config['entries'] ?? [] as $data) {
            /** @var mixed[] $data */
            $entries[] = Entry::createFrom($data);
        }

        return $entries;
    }
}
