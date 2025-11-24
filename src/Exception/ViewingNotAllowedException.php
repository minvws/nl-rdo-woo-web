<?php

declare(strict_types=1);

namespace Shared\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This exception extends the NotFound exception to be handled as a 404. To not disclose that a non-public dossier
 * exists.
 */
class ViewingNotAllowedException extends NotFoundHttpException
{
    public static function forDossier(): self
    {
        return new self('Dossier not found');
    }

    public static function forDossierOrDocument(): self
    {
        return new self('Dossier or document not found');
    }
}
