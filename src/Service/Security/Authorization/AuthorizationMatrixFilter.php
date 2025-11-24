<?php

declare(strict_types=1);

namespace Shared\Service\Security\Authorization;

enum AuthorizationMatrixFilter: string
{
    case ORGANISATION_ONLY = 'org_only';
    case PUBLISHED_DOSSIERS = 'published_dossiers';
    case UNPUBLISHED_DOSSIERS = 'unpublished_dossiers';
}
