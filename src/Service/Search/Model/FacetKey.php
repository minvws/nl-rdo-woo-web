<?php

declare(strict_types=1);

namespace App\Service\Search\Model;

enum FacetKey: string
{
    case DEPARTMENT = 'department';
    case OFFICIAL = 'official';
    case SUBJECT = 'subject';
    case SOURCE = 'source';
    case PERIOD = 'period';
    case GROUNDS = 'grounds';
    case JUDGEMENT = 'judgement';
    case DATE = 'date';
    case DOSSIER_NR = 'dnr';
    case INQUIRY_DOSSIERS = 'dsi';
    case INQUIRY_DOCUMENTS = 'dci';
}
