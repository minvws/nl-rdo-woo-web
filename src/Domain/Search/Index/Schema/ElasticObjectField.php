<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Schema;

enum ElasticObjectField: string
{
    case SUBJECT = 'subject';
    case DEPARTMENTS = 'departments';
}
