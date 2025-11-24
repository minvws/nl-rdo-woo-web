<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Tooi;

/**
 * @phpcs:ignore Generic.Files.LineLength.TooLong
 * Based on https://standaarden.overheid.nl/diwoo/metadata/doc/0.9.4/diwoo-metadata-lijsten_xsd_Simple_Type_diwoo_soorthandelinglijst#soorthandelinglijst
 */
enum SoortHandeling: string
{
    private const BASE_URI = 'https://identifier.overheid.nl/tooi/def/thes/kern/';

    case c_e1ec050e = 'ondertekening';

    case c_dfcee535 = 'ontvangst';

    case c_641ecd76 = 'vaststelling';

    public function getResource(): string
    {
        return self::BASE_URI . $this->name;
    }
}
