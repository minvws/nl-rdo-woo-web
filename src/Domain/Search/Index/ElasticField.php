<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

enum ElasticField: string
{
    case TYPE = 'type';
    case TOPLEVEL_TYPE = 'toplevel_type';
    case SUBLEVEL_TYPE = 'sublevel_type';
    case SUBJECT = 'subject';
    case SUBJECT_ID = 'id';
    case SUBJECT_NAME = 'name';
}
