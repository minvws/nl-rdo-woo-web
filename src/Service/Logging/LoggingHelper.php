<?php

declare(strict_types=1);

namespace App\Service\Logging;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class LoggingHelper
{
    /** @var LoggingTypeInterface[] */
    private array $loggers = [];

    /**
     * @param iterable|LoggingTypeInterface[] $loggingTypes
     */
    public function __construct(
        #[AutowireIterator('woo_platform.logging.type')]
        private readonly iterable $loggingTypes,
    ) {
        foreach ($this->loggingTypes as $loggingType) {
            $this->loggers[$loggingType::class] = $loggingType;
        }
    }

    public function disable(string $loggingTypeClass): void
    {
        $loggingType = $this->getLoggingTypeByClassname($loggingTypeClass);

        if (! $loggingType->isDisabled()) {
            $loggingType->disable();
        }
    }

    public function disableAll(): void
    {
        foreach (array_keys($this->loggers) as $loggingTypeClass) {
            $this->disable($loggingTypeClass);
        }
    }

    public function restore(string $loggingTypeClass): void
    {
        $loggingType = $this->getLoggingTypeByClassname($loggingTypeClass);

        if (! $loggingType->isDisabled()) {
            throw new \RuntimeException("Cannot restore LoggingType $loggingTypeClass, it is not disabled");
        }

        $loggingType->restore();
    }

    public function restoreAll(): void
    {
        foreach (array_keys($this->loggers) as $loggingTypeClass) {
            $this->restore($loggingTypeClass);
        }
    }

    private function getLoggingTypeByClassname(string $className): LoggingTypeInterface
    {
        if (! array_key_exists($className, $this->loggers)) {
            throw new \RuntimeException("No LoggingType of class $className registered");
        }

        return $this->loggers[$className];
    }
}
