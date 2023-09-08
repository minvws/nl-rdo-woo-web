<?php

declare(strict_types=1);

namespace App\Service\Inventory\Reader;

use App\Service\Inventory\MetadataField;

/**
 * Creates an InventoryReaderInterface configured with mapping.
 *
 * For now there is only one use-case, so the mapping is hardcoded. To be refactored when new use-cases are implemented.
 */
class InventoryReaderFactory
{
    public function create(): InventoryReaderInterface
    {
        return new ExcelInventoryReader(...[
            new ColumnMapping(MetadataField::DATE, true, ['date', 'datum']),
            new ColumnMapping(MetadataField::DOCUMENT, true, ['document', 'document id', 'documentnr', 'document nr', 'documentnr.', 'document nr.']),
            new ColumnMapping(MetadataField::FAMILY, true, ['family', 'familie', 'family id']),
            new ColumnMapping(MetadataField::SOURCETYPE, true, ['file type', 'filetype']),
            new ColumnMapping(MetadataField::GROUND, true, ['beoordelingsgrond', 'grond', 'tagmulti006']),
            new ColumnMapping(MetadataField::ID, true, ['id']),
            new ColumnMapping(MetadataField::JUDGEMENT, true, ['beoordeling']),
            new ColumnMapping(MetadataField::PERIOD, true, ['periode', 'period']),
            new ColumnMapping(MetadataField::SUBJECT, true, ['onderwerp', 'subject']),
            new ColumnMapping(MetadataField::THREADID, true, ['thread id', 'email thread id', 'email thread']),
            new ColumnMapping(MetadataField::CASENR, false, ['zaaknr', 'casenr', 'zaak', 'case', 'zaaknummer']),
            new ColumnMapping(MetadataField::SUSPENDED, false, ['opgeschort', 'suspended', 'tag015']),
            new ColumnMapping(MetadataField::SUSPENDED, false, ['opgeschort', 'suspended', 'tag015']),
            new ColumnMapping(MetadataField::LINK, false, ['publiekelinktag', 'publieke link', 'publiekelink']),
            new ColumnMapping(MetadataField::REMARK, false, ['toelichting']),
            new ColumnMapping(MetadataField::MATTER, true, ['matter']),
        ]);
    }
}
