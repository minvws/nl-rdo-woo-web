<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Reader;

use RuntimeException;
use Shared\Service\FileReader\ColumnMapping;
use Shared\Service\FileReader\ReaderFactoryInterface;
use Shared\Service\Inventory\MetadataField;
use Traversable;

use function iterator_to_array;

/**
 * Creates an InventoryReaderInterface configured with mapping.
 * It can be used to read any file format, as long as a ReaderFactoryInterface is provided for that format.
 *
 * For now there is only one use-case, so the mapping is hardcoded. To be refactored when new use-cases are implemented.
 */
class InventoryReaderFactory
{
    /** @var ReaderFactoryInterface[] */
    protected array $factories;

    /**
     * @param ReaderFactoryInterface[] $factories
     */
    public function __construct(iterable $factories)
    {
        $this->factories = $factories instanceof Traversable ? iterator_to_array($factories, false) : $factories;
    }

    public function create(string $mimetype): InventoryReaderInterface
    {
        $readerFactory = $this->findFactoryForFile($mimetype);
        if ($readerFactory === null) {
            throw new RuntimeException('No reader factory found for type ' . $mimetype);
        }

        return new InventoryReader(
            $readerFactory,
            ...[
                new ColumnMapping(
                    name: MetadataField::DATE->value,
                    required: false,
                    columnNames: ['date', 'datum'],
                ),
                new ColumnMapping(
                    name: MetadataField::DOCUMENT->value,
                    required: true,
                    columnNames: [
                        'document',
                        'document id',
                        'documentnr',
                        'document nr',
                        'documentnr.',
                        'document nr.',
                        'nr.',
                        'omschrijving',
                    ],
                ),
                new ColumnMapping(
                    name: MetadataField::FAMILY->value,
                    required: false,
                    columnNames: [
                        'family',
                        'familie',
                        'family id',
                    ],
                ),
                new ColumnMapping(
                    name: MetadataField::SOURCETYPE->value,
                    required: false,
                    columnNames: ['file type', 'filetype'],
                ),
                new ColumnMapping(
                    name: MetadataField::GROUND->value,
                    required: true,
                    columnNames: [
                        'beoordelingsgrond',
                        'grond',
                        'tagmulti006',
                        'beoordelingsgrond (; gescheiden)',
                    ],
                ),
                new ColumnMapping(
                    name: MetadataField::ID->value,
                    required: true,
                    columnNames: ['id'],
                ),
                new ColumnMapping(
                    name: MetadataField::JUDGEMENT->value,
                    required: true,
                    columnNames: ['beoordeling', 'openbaarmaking'],
                ),
                new ColumnMapping(
                    name: MetadataField::THREADID->value,
                    required: false,
                    columnNames: [
                        'thread id',
                        'email thread id',
                        'email thread',
                    ],
                ),
                new ColumnMapping(
                    name: MetadataField::CASENR->value,
                    required: false,
                    columnNames: [
                        'zaaknr',
                        'casenr',
                        'zaak',
                        'case',
                        'zaaknummer',
                    ],
                ),
                new ColumnMapping(
                    name: MetadataField::SUSPENDED->value,
                    required: false,
                    columnNames: ['opgeschort', 'suspended', 'tag015'],
                ),
                new ColumnMapping(
                    name: MetadataField::LINK->value,
                    required: false,
                    columnNames: [
                        'publiekelinktag',
                        'publieke link',
                        'publiekelink',
                        'publicatielink',
                        'publicatie link',
                        'publiekelink ( | gescheiden)',
                    ],
                ),
                new ColumnMapping(
                    name: MetadataField::REMARK->value,
                    required: false,
                    columnNames: ['toelichting', 'opmerking'],
                ),
                new ColumnMapping(
                    name: MetadataField::MATTER->value,
                    required: true,
                    columnNames: [
                        'matter',
                        'matter / marjoleinnummer',
                    ],
                ),
                new ColumnMapping(
                    name: MetadataField::REFERS_TO->value,
                    required: false,
                    columnNames: ['gerelateerd id'],
                ),
            ]
        );
    }

    protected function findFactoryForFile(string $mimetype): ?ReaderFactoryInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($mimetype)) {
                return $factory;
            }
        }

        return null;
    }
}
