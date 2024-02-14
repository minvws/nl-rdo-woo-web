<?php

declare(strict_types=1);

namespace App\Entity;

enum DecisionAttachmentType: string
{
    case WOB_WOO_REQUEST = 'wob_woo_request';
    case WOB_WOO_DECISION = 'woo_woo_decision';
    case DECISION_ON_OBJECTION = 'decision_on_objection';
    case CONVERSION_WOB_WOO = 'conversion_wob_woo';
}
