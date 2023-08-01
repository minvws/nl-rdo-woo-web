<?php

declare(strict_types=1);

namespace App\Service\Logging;

class LoggingHelper
{
    /** @var LoggingTypeInterface[] */
    private array $loggers = [];

    public function __construct(
        /** @var iterable|LoggingTypeInterface[] $loggingTypes */
        private readonly iterable $loggingTypes,
    ) {
        foreach ($this->loggingTypes as $loggingType) {
            $this->loggers[get_class($loggingType)] = $loggingType;
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
