<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Sort;

use App\Domain\Search\Index\Schema\ElasticField;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum SortField: string implements TranslatableInterface
{
    case SCORE = '_score';
    case DECISION_DATE = ElasticField::DECISION_DATE->value;
    case PUBLICATION_DATE = ElasticField::PUBLICATION_DATE->value;

    public static function fromValue(string $input): self
    {
        return self::tryFrom($input) ?? self::SCORE;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('global.' . $this->value, locale: $locale);
    }
}
