<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Tools\Pdftk;

class PdftkRuntimeException extends \RuntimeException
{
    public static function noPageCountResultFound(): self
    {
        return new self('No "NumberOfPages" found in result');
    }
}
