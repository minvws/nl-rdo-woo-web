<?php

declare(strict_types=1);

namespace App\Service\Search\Model;

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
    case PREFIXED_DOSSIER_NR = 'dnr';
    case INQUIRY_DOSSIERS = 'dsi';
    case INQUIRY_DOCUMENTS = 'dci';

    /**
     * @deprecated use FacetDefinition::$requestParameter instead
     */
    public function getParamName(): string
    {
        return match ($this) {
            self::TYPE => 'doctype',
            self::SUBJECT => 'subject',
            self::SOURCE => 'src',
            self::GROUNDS => 'gnd',
            self::JUDGEMENT => 'jdg',
            self::DEPARTMENT => 'dep',
            self::PERIOD => 'prd',
            self::DATE => 'dt',
            self::PREFIXED_DOSSIER_NR => 'dnr',
            self::INQUIRY_DOSSIERS => 'dsi',
            self::INQUIRY_DOCUMENTS => 'dci',
        };
    }
}
