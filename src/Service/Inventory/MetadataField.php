<?php

declare(strict_types=1);

namespace App\Service\Inventory;

enum MetadataField: string
{
    case DATE = 'date';
    case DOCUMENT = 'document';
    case FAMILY = 'family';
    case SOURCETYPE = 'sourcetype';
    case GROUND = 'ground';
    case ID = 'id';
    case JUDGEMENT = 'judgement';
    case THREADID = 'threadid';
    case CASENR = 'casenr';
    case SUSPENDED = 'suspended';
    case LINK = 'link';
    case REMARK = 'remark';
    case MATTER = 'matter';
}
