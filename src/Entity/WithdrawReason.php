<?php

declare(strict_types=1);

namespace App\Entity;

enum WithdrawReason: string
{
    case DATA_IN_DOCUMENT = 'data_in_document';
    case DATA_IN_FILE = 'data_in_file';
    case SUSPENDED_DOCUMENT = 'suspended_document';
    case UNREADABLE_DOCUMENT = 'unreadable_document';
    case INCORRECT_ATTACHMENT = 'incorrect_attachment';
}
