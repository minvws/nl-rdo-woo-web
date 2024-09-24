<?php

declare(strict_types=1);

namespace App\Service\Search\Model;

use App\Service\Search\Query\Facet\Input\DateFacetInput;
use App\Service\Search\Query\Facet\Input\FacetInput;
use App\Service\Search\Query\Facet\Input\StringValuesFacetInput;

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
    case DOSSIER_NR = 'dnr';
    case INQUIRY_DOSSIERS = 'dsi';
    case INQUIRY_DOCUMENTS = 'dci';

    /**
     * @return class-string<FacetInput>
     */
    public function getInputClass(): string
    {
        return match ($this) {
            self::TYPE => StringValuesFacetInput::class,
            self::SUBJECT => StringValuesFacetInput::class,
            self::SOURCE => StringValuesFacetInput::class,
            self::GROUNDS => StringValuesFacetInput::class,
            self::JUDGEMENT => StringValuesFacetInput::class,
            self::DEPARTMENT => StringValuesFacetInput::class,
            self::PERIOD => StringValuesFacetInput::class,
            self::DATE => DateFacetInput::class,
            self::DOSSIER_NR => StringValuesFacetInput::class,
            self::INQUIRY_DOSSIERS => StringValuesFacetInput::class,
            self::INQUIRY_DOCUMENTS => StringValuesFacetInput::class,
        };
    }

    public function getPath(): string
    {
        return match ($this) {
            self::TYPE => 'type',
            self::SUBJECT => 'subject_names',
            self::SOURCE => 'source_type',
            self::GROUNDS => 'grounds',
            self::JUDGEMENT => 'judgement',
            self::DEPARTMENT => 'department_names',
            self::PERIOD => 'date_period',
            self::DATE => 'date_filter',
            self::DOSSIER_NR => 'dossier_nr',
            self::INQUIRY_DOSSIERS => 'inquiry_ids',
            self::INQUIRY_DOCUMENTS => 'inquiry_ids',
        };
    }

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
            self::DOSSIER_NR => 'dnr',
            self::INQUIRY_DOSSIERS => 'dsi',
            self::INQUIRY_DOCUMENTS => 'dci',
        };
    }
}
