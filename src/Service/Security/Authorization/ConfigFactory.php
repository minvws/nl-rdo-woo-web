<?php

declare(strict_types=1);

namespace Shared\Service\Security\Authorization;

use Webmozart\Assert\Assert;

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
            Assert::isArray($data);

            $entries[] = Entry::createFrom($data);
        }

        return $entries;
    }
}
