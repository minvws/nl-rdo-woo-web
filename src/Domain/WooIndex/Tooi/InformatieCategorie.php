<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Tooi;

/**
 * Based on https://standaarden.overheid.nl/diwoo/metadata/doc/0.9.4/diwoo-metadata-lijsten_xsd_Simple_Type_diwoo_informatiecategorielijst#informatiecategorielijst.
 */
enum InformatieCategorie: string
{
    private const BASE_URI = 'https://identifier.overheid.nl/tooi/def/thes/kern/';

    case c_3baef532 = 'Woo-verzoeken en -besluiten';

    case c_99a836c7 = 'adviezen';

    case c_3a248e3a = 'agenda’s en besluitenlijsten bestuurscolleges';

    case c_89ee6784 = 'bereikbaarheidsgegevens';

    case c_46a81018 = 'beschikkingen';

    case c_8c840238 = 'bij vertegenwoordigende organen ingekomen stukken';

    case c_8fc2335c = 'convenanten';

    case c_816e508d = 'inspanningsverplichting art 3.1 Woo';

    case c_c6cd1213 = 'jaarplannen en jaarverslagen';

    case c_a870c43d = 'klachtoordelen';

    case c_fdaee95e = 'onderzoeksrapporten';

    case c_759721e2 = 'ontwerpen van wet- en regelgeving met adviesaanvraag';

    case c_40a05794 = 'organisatie en werkwijze';

    case c_aab6bfc7 = 'overige besluiten van algemene strekking';

    case c_cf268088 = 'subsidieverplichtingen anders dan met beschikking';

    case c_c76862ab = 'vergaderstukken Staten-Generaal';

    case c_db4862c3 = 'vergaderstukken decentrale overheden';

    case c_139c6280 = 'wetten en algemeen verbindende voorschriften';

    public function getResource(): string
    {
        return self::BASE_URI . $this->name;
    }
}
