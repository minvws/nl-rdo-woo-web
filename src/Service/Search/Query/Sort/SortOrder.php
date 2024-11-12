<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Sort;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum SortOrder: string implements TranslatableInterface
{
    case ASC = 'asc';
    case DESC = 'desc';

    public static function fromValue(string $input): self
    {
        return self::tryFrom($input) ?? self::DESC;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('global.sort.' . $this->value, locale: $locale);
    }
}
