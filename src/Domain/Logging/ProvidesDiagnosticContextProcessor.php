<?php

declare(strict_types=1);

namespace Shared\Domain\Logging;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Shared\Domain\Exception\ProvidesDiagnosticContext;
use Throwable;

#[AsMonologProcessor()]
final class ProvidesDiagnosticContextProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $throwable = $record->context['exception'] ?? null;

        if (! $throwable instanceof Throwable) {
            return $record;
        }

        $extra = [];

        for ($e = $throwable; $e !== null; $e = $e->getPrevious()) {
            if ($e instanceof ProvidesDiagnosticContext) {
                $extra += $e->getDiagnosticContext(); // outermost wins on key collision
            }
        }

        return $extra === []
            ? $record
            : $record->with(extra: [...$record->extra, ...$extra]);
    }
}
