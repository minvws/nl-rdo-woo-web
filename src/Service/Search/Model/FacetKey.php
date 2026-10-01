<?php

declare(strict_types=1);

namespace Shared\Service\Search\Model;

enum FacetKey: string
{
    case TYPE = 'type';
    case SUBJECT = 'subject';
    case SOURCE = 'source';
    case GROUNDS = 'grounds';
    case JUDGEMENT = 'judgement';
    case DEPARTMENT = 'department';
    case PERIOD = 'period';
    case DATE = 'date';
    case PREFIXED_DOSSIER_NUMBER = 'dnr';
    case INQUIRY_DOSSIERS = 'dsi';
    case INQUIRY_DOCUMENTS = 'dci';
    case FAMILY = 'fam';
    case THREAD = 'thread';
    case REFERRED_DOCUMENT_NUMBER = 'ref';
}
