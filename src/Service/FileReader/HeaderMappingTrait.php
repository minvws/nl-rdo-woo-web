<?php

declare(strict_types=1);

namespace Shared\Service\FileReader;

use PhpOffice\PhpSpreadsheet\Exception;
use Shared\Exception\FileReaderException;

use function array_filter;
use function array_keys;
use function count;
use function in_array;
use function ltrim;
use function strtolower;
use function trim;

trait HeaderMappingTrait
{
    /**
     * Resolve the header mapping into an array of mapped headers (name => column)
     * Will throw an exception for missing mandatory headers.
     *
     * @param string[] $headers
     * @param ColumnMapping[] $columnMappings
     *
     * @throws FileReaderException|Exception
     */
    private function resolveHeaderMapping(array $headers, array $columnMappings): HeaderMap
    {
        $mapping = [];
        foreach ($columnMappings as $columnMapping) {
            $mapping[$columnMapping->getName()] = $columnMapping;
        }

        $headerMapping = [];
        $missingHeaders = $mapping;

        foreach ($headers as $idx => $columnName) {
            $columnName = strtolower(trim($columnName));
            $columnName = trim(ltrim($columnName, '0123456789'));
            if ($columnName === '') {
                continue;
            }

            foreach ($missingHeaders as $key => $mapping) {
                if (in_array($columnName, $mapping->getColumnNames(), true)) {
                    $headerMapping[$key] = $idx;
                    unset($missingHeaders[$key]);
                }
            }
        }

        $missingHeaders = array_filter(
            $missingHeaders,
            static fn (ColumnMapping $mapping): bool => $mapping->isRequired()
        );

        if (count($missingHeaders) > 0) {
            throw FileReaderException::forMissingHeaders(array_keys($missingHeaders));
        }

        return new HeaderMap($headerMapping);
    }
}
