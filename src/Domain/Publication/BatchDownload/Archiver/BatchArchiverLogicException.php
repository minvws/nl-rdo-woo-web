<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\BatchDownload\Archiver;

use LogicException;

final class BatchArchiverLogicException extends LogicException implements BatchArchiverException
{
}
