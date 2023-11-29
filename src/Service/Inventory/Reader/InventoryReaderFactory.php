<?php

declare(strict_types=1);

namespace App\Service\Inventory\Reader;

use App\Service\Excel\ColumnMapping;
use App\Service\Excel\ExcelReaderFactory;
use App\Service\Inventory\MetadataField;

/**
 * Creates an InventoryReaderInterface configured with mapping.
 *
 * For now there is only one use-case, so the mapping is hardcoded. To be refactored when new use-cases are implemented.
 */
class InventoryReaderFactory
{
    public function __construct(
        private readonly ExcelReaderFactory $excelReaderFactory,
    ) {
    }

    public function create(): InventoryReaderInterface
    {
        return new ExcelInventoryReader(
            $this->excelReaderFactory,
            ...[
                new ColumnMapping(
                    name: MetadataField::DATE->value,
                    required: true,
                    columnNames: ['date', 'datum'],
                ),
                new ColumnMapping(
                    name: MetadataField::DOCUMENT->value,
                    required: true,
                    columnNames: ['document', 'document id', 'documentnr', 'document nr', 'documentnr.', 'document nr.'],
                ),
                new ColumnMapping(
                    name: MetadataField::FAMILY->value,
                    required: true,
                    columnNames: ['family', 'familie', 'family id'],
                ),
                new ColumnMapping(
                    name: MetadataField::SOURCETYPE->value,
                    required: true,
                    columnNames: ['file type', 'filetype'],
                ),
                new ColumnMapping(
                    name: MetadataField::GROUND->value,
                    required: true,
                    columnNames: ['beoordelingsgrond', 'grond', 'tagmulti006'],
                ),
                new ColumnMapping(
                    name: MetadataField::ID->value,
                    required: true,
                    columnNames: ['id'],
                ),
                new ColumnMapping(
                    name: MetadataField::JUDGEMENT->value,
                    required: true,
                    columnNames: ['beoordeling'],
                ),
                new ColumnMapping(
                    name: MetadataField::PERIOD->value,
                    required: true,
                    columnNames: ['periode', 'period'],
                ),
                new ColumnMapping(
                    name: MetadataField::SUBJECT->value,
                    required: true,
                    columnNames: ['onderwerp', 'subject'],
                ),
                new ColumnMapping(
                    name: MetadataField::THREADID->value,
                    required: true,
                    columnNames: ['thread id', 'email thread id', 'email thread'],
                ),
                new ColumnMapping(
                    name: MetadataField::CASENR->value,
                    required: false,
                    columnNames: ['zaaknr', 'casenr', 'zaak', 'case', 'zaaknummer'],
                ),
                new ColumnMapping(
                    name: MetadataField::SUSPENDED->value,
                    required: false,
                    columnNames: ['opgeschort', 'suspended', 'tag015'],
                ),
                new ColumnMapping(
                    name: MetadataField::SUSPENDED->value,
                    required: false,
                    columnNames: ['opgeschort', 'suspended', 'tag015'],
                ),
                new ColumnMapping(
                    name: MetadataField::LINK->value,
                    required: false,
                    columnNames: ['publiekelinktag', 'publieke link', 'publiekelink'],
                ),
                new ColumnMapping(
                    name: MetadataField::REMARK->value,
                    required: false,
                    columnNames: ['toelichting'],
                ),
                new ColumnMapping(
                    name: MetadataField::MATTER->value,
                    required: true,
                    columnNames: ['matter'],
                ),
            ]
        );
    }
}
